<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;

class CountryController extends Controller
{
    public function read(Request $request) {

        $data = $request->validate([
            'nameFilterValue' => 'string|nullable',
        ]);
        $countries = [];

        if (empty($data["nameFilterValue"]) || $data["nameFilterValue"] == null) {
            $countries = Country::orderBy('name', 'asc')->get();
        } else {
            $countries = Country::where('name', 'like', "%{$data["nameFilterValue"]}%")->orderBy('name', 'asc')->get();
        }

        return response($countries);
    }
}
