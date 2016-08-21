<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Meetup;

use Carbon\Carbon;

use JWTAuth;

class MeetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => [
            'store', 'update', 'destroy'
        ]]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetups = Meetup::all();

        foreach ($meetups as $meetup) {
            $meetup->view_meetup = [
                'href' => 'api/v1/meetup/'.$meetup->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all meetups',
            'meetups' => $meetups
        ];

        return response()->json($response, 200);
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
            'title' => 'required|max:100',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meetup = new Meetup([
            'time' => Carbon::createFromFormat('YmdHie', $time),
            'title' => $title,
            'description' => $description
        ]);

        if($meetup->save()) {
            $meetup->users()->attach($user_id);
            $meetup->view_meetup = [
                'href' => 'api/v1/meetup/'.$meetup->id,
                'method' => 'POST'
            ];

            $response = [
                'msg' => 'Meetup created',
                'meetup' => $meetup
            ];

            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'an error occurred',
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meetup = Meetup::with('users')->where('id', $id)->firstOrFail();
        $meetup->view_meetup = [
            'href' => 'api/v1/meetup/'.$meetup->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'meetup information',
            'meetup' => $meetup
        ];

        return response()->json($response, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required|max:100',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
            'user_id' => 'required'
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meetup = Meetup::with('users')->findOrFail($id);

        if(!$meetup->users()->where('users.id', $user_id)->first()) {
            return response()->json([
                'msg' => 'user not registered for the meetup, update not successful'
            ], 401);
        }

        $meetup->time = Carbon::createFromFormat('YmdHie', $time);
        $meetup->title = $title;
        $meetup->description = $description;

        if(!$meetup->update()) {
            return response()->json([
                'msg' => 'update not successful'
            ], 404);
        }

        $meetup->view_meetup = [
            'href' => 'api/v1/meetup/'.$meetup->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'updated successfully',
            'meetup' => $meetup
        ];

        return response()->json($response, 200);
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
                'msg' => 'user not registered for the meetup, update not successful'
            ], 401);
        }

        $users = $meetup->users;
        $meetup->users()->detach();

        if(!$meetup->delete()) {
            foreach ($users as $user) {
                $meetup->users()->attach($user);
            }

            return response()->json([
                'msg' => 'an error occurred'
            ], 404);
        }

        $response = [
            'msg' => 'Meetup deleted',
            'create' => [
                'href' => 'api/v1/meetup',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
