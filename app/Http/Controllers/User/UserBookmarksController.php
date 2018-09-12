<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\APIController;
use App\Models\User\Study\Bookmark;
use Illuminate\Support\Facades\Validator;

class UserBookmarksController extends APIController
{
	/**
	 * Display a listing of the bookmarks.
	 *
	 * @OA\Get(
	 *     path="/users/{user_id}/bookmarks/",
	 *     tags={"User"},
	 *     summary="Returns a list of bookmarks for a specific user",
	 *     description="Returns filtered permissions for a fileset dependent upon your authorization level and API key",
	 *     operationId="v4_bible_filesets_permissions.index",
	 *     @OA\Parameter(name="id", in="path", required=true, description="The fileset ID", @OA\Schema(ref="#/components/schemas/BibleFileset/properties/id")),
	 *     @OA\Parameter(ref="#/components/parameters/version_number"),
	 *     @OA\Parameter(ref="#/components/parameters/key"),
	 *     @OA\Parameter(ref="#/components/parameters/pretty"),
	 *     @OA\Parameter(ref="#/components/parameters/format"),
	 *     @OA\Response(
	 *         response=200,
	 *         description="successful operation",
	 *         @OA\MediaType(
	 *            mediaType="application/json",
	 *            @OA\Schema(ref="#/components/schemas/v4_bible_filesets_permissions.index")
	 *         )
	 *     )
	 * )
	 *
	 * @param int $user_id
	 * @return \Illuminate\Http\Response
	 */
    public function index($user_id)
    {
    	$book_id = checkParam('book_id', null, 'optional');
	    $chapter = checkParam('chapter', null, 'optional');

    	$bookmarks = Bookmark::where('user_id',$user_id)
			->when($book_id, function ($q) use ($book_id) {
			    $q->where('book_id',$book_id);
	        })->when($chapter, function ($q) use ($chapter) {
			    $q->where('chapter',$chapter);
		    })->get();

		return $this->reply($bookmarks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/users/{user_id}/bookmarks/",
     *     tags={"User"},
     *     summary="Create a brand new bookmark for a specific user",
     *     description="Returns filtered permissions for a fileset dependent upon your authorization level and API key",
     *     operationId="v4_bible_filesets_permissions.index",
     *     @OA\Parameter(name="id", in="path", required=true, description="The fileset ID", @OA\Schema(ref="#/components/schemas/BibleFileset/properties/id")),
     *     @OA\Parameter(ref="#/components/parameters/version_number"),
     *     @OA\Parameter(ref="#/components/parameters/key"),
     *     @OA\Parameter(ref="#/components/parameters/pretty"),
     *     @OA\Parameter(ref="#/components/parameters/format"),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(ref="#/components/schemas/v4_bible_filesets_permissions.index")
     *         )
     *     )
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $invalidBookmark = $this->validateBookmark();
        if($invalidBookmark) return $this->setStatusCode(422)->replyWithError($invalidBookmark);

        Bookmark::create(request()->all());
        return $this->reply("Bookmark Created successfully");
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($user_id,$id)
    {
	    $invalidBookmark = $this->validateBookmark();
	    if($invalidBookmark) return $this->setStatusCode(422)->replyWithError($invalidBookmark);

	    $bookmark = Bookmark::where('id',$id)->where('user_id',$user_id)->first();
	    if(!$bookmark) return $this->setStatusCode(404)->replyWithError('Bookmark not found');
	    $bookmark->fill(request()->all());
	    $bookmark->save();

	    return $this->reply("Bookmark Created successfully");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($user_id, $id)
    {
	    $bookmark = Bookmark::where('id',$id)->where('user_id',$user_id)->first();
	    $bookmark->delete();

	    return $this->reply("bookmark successfully deleted");
    }

    private function validateBookmark()
    {
		$validator = Validator::make(request()->all(), [
		    'bible_id'    => 'required|exists:dbp.bibles,id',
		    'user_id'     => 'required|exists:dbp_users.users,id',
		    'book_id'     => 'required|exists:dbp.books,id',
		    'chapter'     => 'required|max:150|min:1|integer',
		    'verse_start' => 'required|max:177|min:1|integer'
		]);
		if ($validator->fails()) return ['errors' => $validator->errors()];
    }
}