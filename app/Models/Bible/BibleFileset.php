<?php

namespace App\Models\Bible;

use App\Models\Organization\Asset;
use App\Models\Organization\Organization;
use App\Models\User\AccessGroupFileset;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Bible\BibleFileset
 * @mixin \Eloquent
 *
 * @method static BibleFileset whereId($value)
 * @property string $id
 * @method static BibleFileset whereHashId($value)
 * @property string $hash_id
 * @method static BibleFileset whereBucketId($value)
 * @property string $asset_id
 * @method static BibleFileset whereSetTypeCode($value)
 * @property string $set_type_code
 * @method static BibleFileset whereSetSizeCode($value)
 * @property string $set_size_code
 * @method static Bible whereCreatedAt($value)
 * @property \Carbon\Carbon|null $created_at
 * @method static Bible whereUpdatedAt($value)
 * @property \Carbon\Carbon|null $updated_at
 *
 * @OA\Schema (
 *     type="object",
 *     description="BibleFileset",
 *     title="Bible Fileset",
 *     @OA\Xml(name="BibleFileset")
 * )
 *
 */
class BibleFileset extends Model
{
    protected $connection = 'dbp';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['created_at','updated_at','response_time','hidden','bible_id','hash_id'];
    protected $fillable = ['name','set_type','organization_id','variation_id','bible_id','set_copyright'];


    /**
     *
     * @OA\Property(
     *   title="id",
     *   type="string",
     *   description="The fileset id",
     *   minLength=6,
     *   maxLength=16
     * )
     *
     */
    protected $id;

    /**
     *
     * @OA\Property(
     *   title="hash_id",
     *   type="string",
     *   description="The hash_id generated from the `asset_id`, `set_type_code`, and `id`",
     *   minLength=12,
     *   maxLength=12
     * )
     *
     */
    protected $hash_id;

    /**
     *
     * @OA\Property(
     *   title="asset_id",
     *   type="string",
     *   description="The asset id of the AWS Bucket or CloudFront instance",
     *   maxLength=64
     * )
     *
     */
    protected $asset_id;

    /**
     *
     * @OA\Property(
     *   title="set_type_code",
     *   type="string",
     *   description="The set_type_code indicating the type of the fileset",
     *   maxLength=3
     * )
     *
     */
    protected $set_type_code;

    /**
     *
     * @OA\Property(
     *   title="set_size_code",
     *   type="string",
     *   description="The set_size_code indicating the size of the fileset",
     *   maxLength=3
     * )
     *
     */
    protected $set_size_code;

    /**
     *
     * @OA\Property(
     *   title="created_at",
     *   type="string",
     *   description="The timestamp at which the fileset was originally created"
     * )
     *
     */
    protected $created_at;

    /**
     *
     * @OA\Property(
     *   title="updated_at",
     *   type="string",
     *   description="The timestamp at which the fileset was last updated"
     * )
     *
     */
    protected $updated_at;

    public function copyright()
    {
        return $this->hasOne(BibleFilesetCopyright::class, 'hash_id', 'hash_id');
    }

    public function copyrightOrganization()
    {
        return $this->hasMany(BibleFilesetCopyrightOrganization::class, 'hash_id', 'hash_id');
    }

    public function permissions()
    {
        return $this->hasMany(AccessGroupFileset::class, 'hash_id', 'hash_id');
    }

    public function bible()
    {
        return $this->hasManyThrough(Bible::class, BibleFilesetConnection::class, 'hash_id', 'id', 'hash_id', 'bible_id');
    }

    public function translations()
    {
        return $this->hasManyThrough(BibleTranslation::class, BibleFilesetConnection::class, 'hash_id', 'bible_id', 'hash_id', 'bible_id');
    }

    public function connections()
    {
        return $this->hasOne(BibleFilesetConnection::class, 'hash_id', 'hash_id');
    }

    public function organization()
    {
        return $this->hasManyThrough(Organization::class, Asset::class, 'id', 'id', 'asset_id', 'organization_id');
    }

    public function files()
    {
        return $this->hasMany(BibleFile::class, 'hash_id', 'hash_id');
    }

    public function meta()
    {
        return $this->hasMany(BibleFilesetTag::class, 'hash_id', 'hash_id');
    }

    public function scopeWithBible($query, $bible_name, $language_id, $organization)
    {
        return $query
            ->join('bible_fileset_connections as connection', 'connection.hash_id', 'bible_filesets.hash_id')
            ->join('bibles', 'connection.bible_id', 'bibles.id', function ($q) use ($language_id) {
                $q->where('bibles.language_id', $language_id);
            })
            ->leftJoin('languages', 'bibles.language_id', 'languages.id')
            ->join('language_translations', function ($q) {
                $q->on('languages.id', 'language_translations.language_source_id')
                  ->on('languages.id', 'language_translations.language_translation_id');
            })
            ->leftJoin('alphabets', 'bibles.script', 'alphabets.script')
            ->leftJoin('bible_translations', function ($q) use ($bible_name) {
                $q->on('bible_translations.bible_id', 'bibles.id')
                  ->where('bible_translations.name', 'LIKE', '%'.$bible_name.'%');
            })
            ->leftJoin('bible_organizations', function ($q) use ($organization) {
                $q->on('bibles.id', 'bible_organizations.bible_id')->where('relationship_type', 'publisher');
                if ($organization) {
                    $q->where('bible_organizations.organization_id', $organization);
                }
            });
    }

    public function scopeUniqueFileset($query, $id = null, $asset_id = null, $fileset_type = null, $ambigious_fileset_type = false)
    {
        return $query->when($id, function ($query) use ($id) {
            $query->where(function ($query) use ($id) {
                $query->where('bible_filesets.id', $id)
                      ->orWhere('bible_filesets.id', substr($id, 0, -4))
                      ->orWhere('bible_filesets.id', 'like', substr($id, 0, -2).'%');
            });
        })
        ->when($asset_id, function ($query) use ($asset_id) {
            $query->where('bible_filesets.asset_id', $asset_id);
        })
        ->when($fileset_type, function ($query) use ($fileset_type, $ambigious_fileset_type) {
            if ($ambigious_fileset_type) {
                $query->where('bible_filesets.set_type_code', 'LIKE', $fileset_type.'%');
            } else {
                $query->where('bible_filesets.set_type_code', $fileset_type);
            }
        });
    }

    public function scopeLanguage()
    {
        // return
    }
}
