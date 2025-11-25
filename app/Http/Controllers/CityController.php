<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function read(Request $request, $id)
    {
        $country = Country::find($id);
        $cities = [];

        if ($country == null) {
            return response('Країни не знайдено', 404);
        }

        $data = $request->validate([
            'nameFilterValue' => 'string|nullable',
        ]);

        if (empty($data['nameFilterValue']) || $data['nameFilterValue'] == null) {
            $cities = $country->cities()->orderBy('name', 'asc')->get();
        } else {
            $cities = $country->cities()->where('name', 'like', "%{$data['nameFilterValue']}%")->orderBy('name', 'asc')->get();
        }

        return response($cities);
    }
}
