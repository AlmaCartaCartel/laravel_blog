<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use Sluggable;

    const IS_DRAFT = 0;
    const IS_PUBLIC = 1;

    protected $fillable = [
        'title',
        'content',
        'date'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getCategoryTitle()
    {
        return ($this->category !== null)
            ? $this->category->title
            : 'Нет категории';
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'post_tags',
            'post_id',
            'tag_id'
        );
    }
    public function getTagsTitles(){
        return (!$this->tags->isEmpty())
            ? implode(', ', $this->tags->pluck('title')->all())
            : 'Без тегов';
    }

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }

    public static function add($fields)
    {
        $post = new static;
        $post->fill($fields);
        $post->user_id = 1;
        $post->save();

        return $post;
    }

    public function edit($fields)
    {
        $this->fill($fields);
        $this->save();
    }

    public function remove()
    {
        if($this->image !== null){
            Storage::delete('uploads/' . $this->image);
        }
        $this->delete();
    }

    public function uploadImage($image = null)
    {
        if ($image === null) return;
        if($this->image !== null){
            Storage::delete('uploads/' . $this->image);
        }
        $filename = str_random(10) . '.' . $image->extension();
        $image->storeAs('uploads', $filename);

        $this->image = $filename;
        $this->save();
    }

    public function getImage()
    {
        if ($this->image === null) {
            return 'img/no-image.png';
        }
        return '/uploads/' . $this->image;
    }

    public function setCategory($id = null)
    {
        if ($id === null) return;

        $this->category_id = $id;
        $this->save();
    }

    public function setTags($ids)
    {
        if ($ids === null) return;

        $this->tags()->sync($ids);
    }

    public function setDraft()
    {
        $this->status = Post::IS_DRAFT;
        $this->save();
    }

    public function setPublic()
    {
        $this->status = Post::IS_PUBLIC;
        $this->save();
    }

    public function toggleStatus($value = null)
    {
        if ($value === null) {
            return $this->setDraft();
        } else {
            return $this->setPublic();
        }
    }

    public function setFeatured()
    {
        $this->is_featured = 0;
        $this->save();
    }

    public function setStandart()
    {
        $this->is_featured  = 1;
        $this->save();
    }

    public function toggleFeatured($value = null)
    {
        if ($value === null) {
            return $this->setFeatured();
        } else {
            return $this->setStandart();
        }
    }

    public function setDateAttribute($value)
    {
        $date = Carbon::createFromFormat('d/m/y',$value)->format('Y-m-d');
        $this->attributes['date'] = $date;
    }
}
