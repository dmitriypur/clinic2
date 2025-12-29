<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormStoreRequest;
use Clinic;
use Illuminate\Contracts\View\View;

class FormController extends Controller
{
    public function show(): View
    {
        return view('form');
    }

    public function store(FormStoreRequest $request): void
    {
        Clinic::sendForm($request->validated());
    }
}
