<?php

namespace App\Http\Controllers;

use WebSocket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class DownloadCurrencyController extends Controller
{
    /**
     * Create a new controller instance.
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
     * @param  string  $api_token
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
                            if ($one_symbol['categoryName'] == 'FX' and $one_symbol['groupName'] == 'Major') {

                                //Records of the number of calls of a given token
                                DB::table('AccessTokens')
                                    ->where('token', '=', $api_token)
                                    ->update([
                                        'actual_used' => DB::raw('actual_used+1'),
                                        'updated_at' => Carbon::now()
                                    ]);

                                //Adding data to the database.
                                $return = DB::table('AllForexSymbols')->insert([
                                    'symbol' => $one_symbol['symbol'],
                                    'currency' => $one_symbol['currency'],
                                    'categoryName' => $one_symbol['categoryName'],
                                    'groupName' => $one_symbol['groupName'],
                                    'description' => $one_symbol['description'],
                                    'bid' => $one_symbol['bid'],
                                    'ask' => $one_symbol['ask'],
                                    'high' => $one_symbol['high'],
                                    'low' => $one_symbol['low'],
                                    'time' => Carbon::createFromTimestampMs($one_symbol['time'])->format('Y-m-d H:i:s'),
                                    'created_at' => Carbon::now()
                                ]);
                            }
                        }
                        if ($return == true) {
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
            }else{
                return response()->json(['status' => false, 'message' => 'Incorrect login information']);
            }
        }
    }
}
