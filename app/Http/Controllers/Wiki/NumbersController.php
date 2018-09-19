<?php

namespace App\Http\Controllers\Wiki;

use App\Http\Controllers\APIController;

use App\Models\Language\Alphabet;
use App\Models\Language\Language;

use App\Models\Language\NumeralSystem;
use App\Transformers\AlphabetTransformer;
use App\Transformers\NumbersTransformer;

use Illuminate\Http\Request;

class NumbersController extends APIController
{

	/**
	 *
	 *
	 * @OA\Get(
	 *     path="/numbers/range",
	 *     tags={"Languages"},
	 *     summary="Return a range of numbers",
	 *     description="This route returns the vernacular numbers for a set range.
	The range for a single call is limited to 2000 numbers.",
	 *     operationId="v4_numbers.range",
	 *     @OA\Parameter(ref="#/components/parameters/version_number"),
	 *     @OA\Parameter(ref="#/components/parameters/key"),
	 *     @OA\Parameter(ref="#/components/parameters/pretty"),
	 *     @OA\Parameter(ref="#/components/parameters/format"),
	 *     @OA\Parameter(name="iso", in="query", required=true,
	 *          @OA\Schema(ref="#/components/schemas/Language/properties/iso")),
	 *     @OA\Parameter(name="start", in="query", required=true,
	 *          @OA\Schema(type="object")),
	 *     @OA\Parameter(name="end", in="query", required=true,
	 *          @OA\Schema(type="object")),
	 *     @OA\Response(
	 *         response=200,
	 *         description="successful operation",
	 *         @OA\MediaType(mediaType="application/json", @OA\Schema(ref="#/components/schemas/v4_numbers_range")),
	 *         @OA\MediaType(mediaType="application/xml",  @OA\Schema(ref="#/components/schemas/v4_numbers_range")),
	 *         @OA\MediaType(mediaType="text/x-yaml",      @OA\Schema(ref="#/components/schemas/v4_numbers_range"))
	 *     )
	 * )
	 *
	 * @return mixed
	 *
	 * @OA\Schema (
	 *     type="object",
	 *     schema="v4_numbers_range",
	 *     description="The numbers range return",
	 *     title="The numbers range return",
	 *     @OA\Xml(name="v4_numbers_range"),
	 *     @OA\Property(property="numeral", type="string"),
	 *     @OA\Property(property="numeral_vernacular", type="string")
	 * )
	 *
	 */
	public function customRange()
	{
		$iso    = checkParam('iso');
		$script = checkParam('script');
		$start  = checkParam('start');
		$end    = checkParam('end');
		if (($end - $start) > 2000) return $this->replyWithError(trans('api.numerals_range_error_maxsize', ['num' => $end]));
		$out_numbers = [];
		// Fetch Numbers By Iso Or Script Code
		$numbers = AlphabetNumber::where('script_id', $script)->where('iso', $iso)->get()->keyBy('numeral')->ToArray();
		// Run through the numbers and return the vernaculars
		$current_number = $start;
		while ($end >= $current_number) {
			$number_vernacular = "";
			foreach (str_split($current_number) as $i) {
				$number_vernacular .= (isset($numbers[$i]['numeral_vernacular'])) ? $numbers[$i]['numeral_vernacular'] : $i;
			}
			$out_numbers[] = [
				"numeral"            => intval($current_number),
				"numeral_vernacular" => !empty($numbers) ? $number_vernacular : $current_number,
			];
			$current_number++;
		}
		return $this->reply($out_numbers);
	}

	/**
	 *
	 * @OA\Get(
	 *     path="/numbers",
	 *     tags={"Languages"},
	 *     summary="Return a all Alphabets that have a custom number sets",
	 *     description="Returns a range of numbers",
	 *     operationId="v4_numbers.index",
	 *     @OA\Parameter(ref="#/components/parameters/version_number"),
	 *     @OA\Parameter(ref="#/components/parameters/key"),
	 *     @OA\Parameter(ref="#/components/parameters/pretty"),
	 *     @OA\Parameter(ref="#/components/parameters/format"),
	 *     @OA\Response(
	 *         response=200,
	 *         description="successful operation",
	 *         @OA\MediaType(mediaType="application/json",
	 *         @OA\Schema(ref="#/components/schemas/v4_alphabets_all_response")),
	 *         @OA\MediaType(mediaType="application/xml",
	 *         @OA\Schema(ref="#/components/schemas/v4_alphabets_all_response")),
	 *         @OA\MediaType(mediaType="text/x-yaml",
	 *         @OA\Schema(ref="#/components/schemas/v4_alphabets_all_response"))
	 *     )
	 * )
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
	 */
	public function index()
	{
		if (!$this->api) return view('wiki.languages.alphabets.numerals.index');
		if(env('APP_DEBUG')) \Cache::forget('v4_numbers.index');
		$numeral_systems = \Cache::remember('v4_numbers.index', 1600, function () {
			$numeral_systems = NumeralSystem::with('alphabets')->get();
			return fractal($numeral_systems, new NumbersTransformer())->serializeWith($this->serializer);
		});

		return $this->reply($numeral_systems);
	}

	/**
	 *
	 * @OA\Get(
	 *     path="/numbers/{id}",
	 *     tags={"Languages"},
	 *     summary="Return a single custom number set",
	 *     description="Returns a range of numbers",
	 *     operationId="v4_numbers.show",
	 *     @OA\Parameter(name="id", in="path", required=true, description="The Alphabet id",
	 *          @OA\Schema(ref="#/components/schemas/Alphabet/properties/script")),
	 *     @OA\Parameter(ref="#/components/parameters/version_number"),
	 *     @OA\Parameter(ref="#/components/parameters/key"),
	 *     @OA\Parameter(ref="#/components/parameters/pretty"),
	 *     @OA\Parameter(ref="#/components/parameters/format"),
	 *     @OA\Response(
	 *         response=200,
	 *         description="successful operation",
	 *         @OA\MediaType(mediaType="application/json",
	 *              @OA\Schema(ref="#/components/schemas/v4_alphabets_one_response")),
	 *         @OA\MediaType(mediaType="application/xml",
	 *              @OA\Schema(ref="#/components/schemas/v4_alphabets_one_response")),
	 *         @OA\MediaType(mediaType="text/x-yaml",
	 *              @OA\Schema(ref="#/components/schemas/v4_alphabets_one_response"))
	 *     )
	 * )
	 *
	 * @param $system
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
	 */
	public function show($system)
	{
		if (!$this->api) return view('wiki.languages.alphabets.numerals.show');

		$numerals = NumeralSystem::where('id',$system)->first();
		if(!$numerals) {
			$error_message = trans('api.alphabet_numerals_errors_404', ['script' => $system], $GLOBALS['i18n_iso']);
			return $this->setStatusCode(404)->replyWithError($error_message);
		}

		$cache_string = 'v4_numbers.show'.$system;
		if(env('APP_DEBUG')) \Cache::forget($cache_string);
		$numerals = \Cache::remember($cache_string, 1600, function () use($numerals) {
			$numerals->load('alphabets','numerals');
			return fractal($numerals, new NumbersTransformer())->serializeWith($this->serializer);
		});

		return $this->reply($numerals);
	}

	public function create()
	{
		$alphabets = Alphabet::select('script')->get();
		$languages = Language::select(['iso', 'name'])->get();

		return view('wiki.languages.alphabets.numerals.create', compact('alphabets', 'languages'));
	}

	public function store(Request $request)
	{
		$this->validateNumericSystem($request);

		foreach ($request->numerals as $input_numeral) {
			$alphabetExists = AlphabetNumber::where([
				['script_id', $input_numeral['script_id']],
				['iso', $input_numeral['iso']],
				['numeral', $input_numeral['numeral']],
				['numeral_vernacular', $input_numeral['numeral_vernacular']],
				['numeral_written', $input_numeral['numeral_written']],
			])->first();
			if ($alphabetExists == null) continue;
			$alphabet_number = new AlphabetNumber();
			$alphabet_number->create($input_numeral);
		}
		$script_id = collect($request->numerals)->pluck('script_id')->first();

		return redirect()->route('view_numbers.show', ['system' => $script_id]);
	}

	public function edit($system)
	{
		$alphabets = Alphabet::select('script')->get();
		$languages = Language::select(['iso', 'name'])->get();
		$numbers   = AlphabetNumber::where('script_id', $system)->get();
		if ($this->api) {
			return $numbers;
		}

		return view('wiki.languages.alphabets.numerals.edit', compact('alphabets', 'languages', 'numbers', 'system'));
	}

	public function update()
	{
		// TODO: Update Code
	}

	private function validateNumericSystem(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'script'              => ($request->method() == "POST") ? 'required|unique:dbp.alphabets,script|max:4|min:4' : 'required|exists:dbp.alphabets,script|max:4|min:4',
			'iso'                 => 'exists:dbp.languages,iso',
			'unicode_pdf'         => 'url|nullable',
			'family'              => 'string|max:191|nullable',
			'type'                => 'string|max:191|nullable',
			'white_space'         => 'string|max:191|nullable',
			'open_type_tag'       => 'string|max:191|nullable',
			'status'              => 'string|max:191|nullable',
			'baseline'            => 'string|max:191|nullable',
			'ligatures'           => 'string|max:191|nullable'
		]);

		if ($validator->fails()) {
			if (!$this->api) return redirect('dashboard/numbers/create')->withErrors($validator)->withInput();
			return $this->setStatusCode(422)->replyWithError($validator->errors());
		}
		return false;
	}

}
