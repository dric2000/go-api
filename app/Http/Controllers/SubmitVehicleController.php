<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResponseSubmitVehicleRequest;
use App\Http\Requests\SubmitVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class SubmitVehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use AuthorizesRequests, ValidatesRequests;

    public function index(Request $request)
    {
        $vehicles = $request->user()->driverProfile->vehicles;

        return VehicleResource::collection($vehicles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SubmitVehicleRequest $request)
    {
        $path = $request->file('photo')->store('vehicles_images/' . $request->user()->driverProfile->id); 

        $vehicle = $request->user()->driverProfile->vehicles()->create([
            'modele' => $request->modele,
            'marque' => $request->marque,
            'plaque_number' => $request->plaque_number,
            'photo_path' => $path,
        ]); 

        return response()->json([
            'message' => "Votre véhicule a été soumis.", 
            'vehicle' => new VehicleResource($vehicle)
        ], 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResponseSubmitVehicleRequest $request, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle); 
        $vehicle->update([
            'status_verification' => $request->validated('status_verification')
        ]); 

        return response()->json([
            'message' => "Votre véhicule a été approuvé", 
            'vehicle' => new VehicleResource($vehicle)
        ]); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
