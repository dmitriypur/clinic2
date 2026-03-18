<?php

namespace App\Http\Controllers;

use App\Enums\PageType;
use App\Models\Doctor;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $handle): View
    {
        $doctor = Doctor::query()
            ->publiclyVisible()
            ->where(function ($query) use ($handle) {
                $query->where('handle', $handle)
                    ->orWhere('id', $handle);
            })
            ->with('media')
            ->first();

        if (!$doctor) {
            abort(404);
        }

        $doctor = $doctor->withResolvedCitySeoVariables();

        $doctorsPage = Page::query()
            ->where('type', PageType::Doctors)
            ->firstOrFail()
            ->withResolvedCitySeoVariables();

        return view('doctors.show', compact('doctor', 'doctorsPage'));
    }
}
