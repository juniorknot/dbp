<?php

namespace App\Http\Controllers;

use App\Models\User\Key;
use App\Models\User\Project;
use Illuminate\Http\Request;
use App\Transformers\ProjectTransformer;

use Validator;

class ProjectsController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $user = \Auth::user() ?? Key::find($this->key)->user;
	    if(!$this->api) {
	        if(!$user) return $this->setStatusCode(401)->replyWithError("you're not logged in");
        	return view('dashboard.projects.index',compact('user'));
        }

	    $projects = ($user->admin) ? Project::all() : $user->projects;
	    return $this->reply(fractal()->transformWith(ProjectTransformer::class)->collection($projects));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.projects.create');
    }


	/**
	 *
	 * Store a new Project
	 *
	 * @param Request $request
	 *
	 * @return $this|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function store(Request $request)
    {
	    $user = \Auth::user() ?? Key::find($this->key)->user;
	    if(!$user) return $this->setStatusCode(401)->replyWithError("you're not logged in");

	    $validator = Validator::make($request->all(), [
		    'id'   => 'required|unique:projects,id|max:24',
		    'name' => 'required',
	    ]);

	    if ($validator->fails()) {
		    if($this->api)  return $this->setStatusCode(422)->replyWithError($validator->errors());
		    if(!$this->api) return redirect('dashboard/projects/create')->withErrors($validator)->withInput();
	    }
	    $project = \DB::transaction(function () use($request,$user) {
	        $project = Project::create([
				'id'              => $request->id,
				'name'            => $request->name,
				'url_avatar'      => $request->url_avatar,
				'url_avatar_icon' => $request->url_avatar_icon,
				'url_site'        => $request->url_site,
				'description'     => $request->description,
	        ]);
	        $project->members()->attach($user->id, ['role' => 'member','admin' => 1]);
	        return $project;
	    });

	    if(!$this->api) return view('dashboard.projects.show', compact('user', 'project'));
	    return fractal()->transformWith(ProjectTransformer::class)->item($project)->addMeta(['message' => 'Project Creation Successful']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
	    $user = \Auth::user() ?? Key::find($this->key)->user;
	    if(!$user) return $this->setStatusCode(401)->replyWithError("you're not logged in");

	    $project = Project::find($id);
	    if(!$project) return $this->setStatusCode(404)->replyWithError("Project Not found");

	    $access_allowed = ($project->members->contains($user) OR $user->admin) ? true : false;
	    if(!$access_allowed) return $this->setStatusCode(404)->replyWithError("Access Not allowed");

	    if(!$this->api) return view('dashboard.projects.show',compact('user','project'));
	    return $this->reply(fractal()->transformWith(ProjectTransformer::class)->item($project));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
	    return view('dashboard.projects.edit');
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
	    $user = \Auth::user() ?? Key::find($this->key)->user;
	    if(!$user) return $this->setStatusCode(401)->replyWithError("you're not logged in");

	    $project = Project::find($id);
	    if(!$project) return $this->setStatusCode(404)->replyWithError("Project Not found");

	    $access_allowed = ($project->members->contains($user) OR $user->admin) ? true : false;
	    if(!$access_allowed) return $this->setStatusCode(404)->replyWithError("Access Not allowed");

	    $project->update($request->all());
	    $project->save();

	    return $this->reply("successful");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
	    $user = \Auth::user() ?? Key::find($this->key)->user;
	    if(!$user) return $this->setStatusCode(401)->replyWithError("you're not logged in");

	    $project = Project::find($id);
	    if(!$project) return $this->setStatusCode(404)->replyWithError("Project Not found");

	    $access_allowed = $project->admins->contains($user->id);
	    if(!$access_allowed) return $this->setStatusCode(404)->replyWithError("You must be an admin to delete a project");

	    $project->delete();
        return $this->reply("project deleted");
    }
}