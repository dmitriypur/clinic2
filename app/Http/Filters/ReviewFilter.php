<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;

class ReviewFilter extends AbstractFilter
{

    const DOCTORS = 'doctors';
    const RESOURCES = 'resources';
    const SERVICES = 'services';
    protected function getCallbacks(): array
    {
        return [
            self::DOCTORS => [$this, 'doctors'],
            self::RESOURCES => [$this, 'resources'],
            self::SERVICES => [$this, 'services'],
        ];
    }

    protected function doctors(Builder $builder, $value){
        $builder->whereIn('doctor_id', $value);
    }
    protected function resources(Builder $builder, $value){
        $builder->whereIn('resource', $value);
    }
    protected function services(Builder $builder, $value){
        $builder->whereHas('pages', function ($b) use ($value){
            $b->whereIn('page_id', $value);
        });
    }
}
