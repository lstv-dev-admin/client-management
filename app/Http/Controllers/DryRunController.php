<?php

namespace App\Http\Controllers;

use App\Support\DryRun;
use Illuminate\Http\RedirectResponse;

class DryRunController extends Controller
{
    public function toggle(): RedirectResponse
    {
        $enabled = DryRun::toggle();

        return back()->with(
            'status',
            $enabled
                ? 'Dry run is on. Actions will show what would change, then nothing is saved.'
                : 'Dry run is off. Actions will save to the database again.'
        );
    }
}
