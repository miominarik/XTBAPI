<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TokenController extends Controller
{

    /**
     *Main Function to generate a new API key
     */
    public function GenerateNewToken()
    {
        $return = $this->GenerateToken();

        if ($return != false) {
            return response()->json(['status' => true, 'message' => 'API key added successfully', 'api_key' => $return]);
        } else {
            return response()->json(['status' => false, 'message' => 'The API key could not be generated']);
        }
    }

    //Generate a new API key
    public function GenerateToken()
    {
        $new_token = hash('sha3-512', bin2hex(random_bytes(255)));

        $checkNewToken = $this->CheckNewTokenDB($new_token);

        if ($checkNewToken == true) {
            return $this->SaveNewTokenDB($new_token);
        } else {
            $this->GenerateNewToken();
        }
    }

    //Verify that the API key no longer exists
    public function CheckNewTokenDB($new_token)
    {
        $count = DB::table('AccessTokens')
            ->where('token', '=', $new_token)
            ->count();

        if ($count == 0) {
            return true;
        } else {
            return false;
        }
    }

    //Save the new API key to the database
    public function SaveNewTokenDB($new_token)
    {
        $save = DB::table('AccessTokens')->insert([
            'token' => $new_token,
            'enabled' => true,
            'actual_used' => 0,
            'created_at' => Carbon::now()
        ]);

        if ($save == true) {
            return $new_token;
        } else {
            return false;
        }
    }
}
