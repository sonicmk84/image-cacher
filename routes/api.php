<?php

use App\Models\Pornstar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/pornstars', function (Request $request) {
    $perPage = $request->query('per_page', 15);
    return Pornstar::with('thumbnails')->paginate($perPage);
});

Route::get('/pornstars/{id}', function ($id) {
    return Pornstar::with('thumbnails')->findOrFail($id);
});

Route::post('/pornstars', fn (Request $r) => Pornstar::create($r->all()));
Route::put('/pornstars/{id}', fn (Request $r, $id) => tap(Pornstar::findOrFail($id))->update($r->all()));
Route::delete('/pornstars/{id}', fn ($id) => Pornstar::destroy($id));
