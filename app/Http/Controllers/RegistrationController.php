<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Meetup;

use JWTAuth;

class RegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'meetup_id' => 'required',
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }

        $meetup_id = $request->input('meetup_id');
        $user_id = $user->id;

        $meetup = Meetup::findOrFail($meetup_id);

        $message = [
            'msg' => 'User is already registered for the meetup',
            'user' => $user,
            'meetup' => $meetup,
            'unregister' => [
                'href' => 'api/v1/meetup/registration/'.$meetup->id,
                'method' => 'DELETE',

            ]
        ];

        if($meetup->users()->where('users.id', $user->id)->first()){
            return response()->json($message, 404);
        }

        $user->meetups()->attach($meetup);

        $response = [
            'msg' => 'User registered for the meetup',
            'user' => $user,
            'meetup' => $meetup,
            'unregister' => [
                'href' => 'api/v1/meetup/registration/'.$meetup->id,
                'method' => 'DELETE',
            ]
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meetup = Meetup::findOrFail($id);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }

        if(!$meetup->users()->where('users.id', $user->id)->first()) {
            return response()->json([
                'msg' => 'user not registered for the meetup, delete not successful'
            ], 401);
        }

        $meetup->users()->detach($user->id);

        $response = [
            'msg' => 'User unregistered for meetup',
            'meetup' => $meetup,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meetup/registration',
                'method' => 'POST',
                'params' => 'meetup_id, user_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
