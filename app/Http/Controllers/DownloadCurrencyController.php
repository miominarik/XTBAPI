<?php

namespace App\Http\Controllers;

use WebSocket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class DownloadCurrencyController extends Controller
{

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->login_message = json_encode([
            'command' => 'login',
            'arguments' => [
                'userId' => env('XTB_LOGIN'),
                'password' => env('XTB_PASSWORD')
            ]
        ]);

        $this->all_symbols_message = json_encode([
            'command' => 'getAllSymbols'
        ]);

        $this->logout_message = json_encode([
            'command' => 'logout'
        ]);
    }

    /**
     * Obtain current exchange rates of major currency pairs and save to the database.
     *
     * @param string $api_token
     */
    public function GetMajorForexCurrency($api_token)
    {
        //Check if the request contains an API key
        if (isset($api_token) && !empty($api_token)) {

            //Check that the API key is correct and that it is not blocked
            $data_from_db = DB::table('AccessTokens')
                ->where('token', '=', $api_token)
                ->where('enabled', '=', 1)
                ->count();

            if ($data_from_db > 0) {

                //Connection to XTB API server and authentication of login data
                $client = new WebSocket\Client(env('XTB_SERVER'));
                $client->text($this->login_message);
                $login_status = json_decode($client->receive());

                //If the connection is established, it continues
                if ($login_status->status == true) {

                    //Sending a request to obtain all currency pairs. Store data in a variable.
                    $client->text($this->all_symbols_message);
                    $all_symbols = $client->receive();

                    //Sending a request to log out of the XTB server and end the communication
                    $client->text($this->logout_message);
                    $client->close();

                    if (!empty($all_symbols)) {
                        $all_symbols = json_decode($all_symbols, true);

                        foreach ($all_symbols['returnData'] as $one_symbol) {
                            //Get only Forex and Major
                            if ($one_symbol['categoryName'] == 'CRT' and $one_symbol['groupName'] == 'Crypto' and $one_symbol['symbol'] == 'CARDANO') {

                                //Records of the number of calls of a given token
                                DB::table('AccessTokens')
                                    ->where('token', '=', $api_token)
                                    ->update([
                                        'actual_used' => DB::raw('actual_used+1'),
                                        'updated_at' => Carbon::now()
                                    ]);

                                //Get data from previous row in tablet
                                $last_row = DB::table('AllForexSymbols')
                                    ->select(['id', 'bid', 'close'])
                                    ->where('symbol', '=', $one_symbol['symbol'])
                                    ->orderBy('id', 'DESC')
                                    ->first();

                                if (isset($last_row->id) && !empty($last_row->id)) {
                                    //Update close price in previous row
                                    DB::table('AllForexSymbols')
                                        ->where('id', '=', $last_row->id)
                                        ->update([
                                            'close' => $one_symbol['bid']
                                        ]);

                                    //Calculate TR
                                    $tr = $this->TrCalculation($one_symbol['symbol'], $one_symbol['bid']);

                                    //Calculate ATR
                                    $atr = $this->AtrCalculation($one_symbol['symbol'], $tr);
                                };

                                //Adding data to the database.
                                $return_id = DB::table('AllForexSymbols')->insertGetId([
                                    'symbol' => $one_symbol['symbol'],
                                    'currency' => $one_symbol['currency'],
                                    'categoryName' => $one_symbol['categoryName'],
                                    'groupName' => $one_symbol['groupName'],
                                    'open' => isset($last_row->bid) ? $last_row->bid : null,
                                    'bid' => $one_symbol['bid'],
                                    'high' => $one_symbol['high'],
                                    'low' => $one_symbol['low'],
                                    'time' => Carbon::createFromTimestampMs($one_symbol['time'])->format('Y-m-d H:i:s'),
                                    'created_at' => Carbon::now()
                                ]);

                                if (isset($last_row->id) && !empty($last_row->id)) {
                                    //TR save
                                    DB::table('AllForexSymbols')
                                        ->where('id', '=', $return_id)
                                        ->update([
                                            'tr' => $tr,
                                            'atr' => $atr
                                        ]);
                                }
                            }
                        }
                        if ($return_id) {
                            return response()->json(['status' => true, 'message' => 'The data was successfully imported into the database.']);
                        } else {
                            return response()->json(['status' => false, 'message' => 'Failed to import data into database']);
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'Empty response from XTB API server']);
                    }
                } else {
                    return response()->json(['status' => false, 'message' => 'Incorrect XTB API login information']);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Incorrect login information']);
            }
        }
    }

    /**
     * Calculates and returns the TR value
     *
     * @param  string $symbol
     * @param  float $close_prev
     * @return int/float/null
     */
    public function TrCalculation($symbol, $close_prev)
    {
        $values = DB::table('AllForexSymbols')
            ->select('high', 'low')
            ->where('symbol', '=', $symbol)
            ->orderBy('id', 'DESC')
            ->first();

        if (isset($values->high) && isset($values->low) && !empty($values->high) && !empty($values->low)) {
            return max(($values->high - $values->low), abs($values->high - $close_prev), abs($values->low - $close_prev));
        } else {
            return null;
        };
    }

    /**
     * Calculates and returns the ATR value
     *
     * @param string $symbol
     * @return int/float/null
     */
    public function AtrCalculation($symbol, $actual_tr)
    {
        $atr_n_value = DB::table('Settings')
            ->select('value')
            ->where('name', 'atr_n_value')
            ->first();

        if (isset($atr_n_value->value) && !empty($atr_n_value->value)) {
            $last_atr = DB::table('AllForexSymbols')
                ->select('atr')
                ->where('symbol', '=', $symbol)
                ->orderBy('id', 'DESC')
                ->first();

            if (isset($last_atr->atr) && !empty($last_atr->atr)) {
                return ($last_atr->atr * ($atr_n_value->value - 1) + $actual_tr) / $atr_n_value->value;
            } else {
                $sum_arr = array();

                $values = DB::table('AllForexSymbols')
                    ->select('tr')
                    ->where('symbol', '=', $symbol)
                    ->orderBy('id', 'DESC')
                    ->limit($atr_n_value->value)
                    ->get();

                foreach ($values as $value) {
                    array_push($sum_arr, $value->tr);
                }

                if (isset($sum_arr) && !empty($sum_arr)) {
                    if (count($sum_arr) >= $atr_n_value->value) {
                        return (1 / $atr_n_value->value) * array_sum($sum_arr);
                    } else {
                        return null;
                    }
                } else {
                    return null;
                };
            };
        } else {
            return null;
        };
    }
}
