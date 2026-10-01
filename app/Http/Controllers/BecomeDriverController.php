<?php

namespace App\Http\Controllers;

use App\Http\Requests\BecomeDriverRequest;
use App\Http\Requests\ResponseBecomeDriverRequest;
use App\Http\Resources\DriverProfileResource;
use App\Jobs\SendDriverApprovedEmailJob;
use App\Models\DriverProfile;
use Illuminate\Http\Request;

class BecomeDriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BecomeDriverRequest $request)
    {
        if (! $request->user()->is_identity_verified) {
            return response()->json(['message' => 'Vous devez avoir un compte vérifié pour devenir conducteur'], 403); 
        }

        if ($request->user()->driverProfile) {
            return response()->json(['message' => 'Vous avez déjà un profil conducteur'], 403); 
        }

        $driver = DriverProfile::create([
            'user_id' => $request->user()->id, 
            'license_number' => $request->license_number
        ]);

        $driver->wallet()->create([
            'solde_bloque' => 0, 
            'solde_disponible' => 0,
        ]); 

        return response()->json(new DriverProfileResource($driver)); 
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
    public function update(ResponseBecomeDriverRequest $request, DriverProfile $driverProfile)
    {
        $driverProfile->update([
            'status_verification' => $request->validated('status_verification')
        ]); 

        if ($driverProfile->wasChanged('status_verification') && $driverProfile->status_verification === "approved") {
            SendDriverApprovedEmailJob::dispatch($driverProfile->user); 
        }

        if ($request->status_verification === 'rejected') {
            return response()->json([
                'message' => 'La demande de création de profil conducteur a été rejetée'
            ]); 
        }

        return response()->json([
            'message' => 'Vous êtes maintenant un conducteur. Vous pouvez créer des trajets', 
            'Votre profil' => new DriverProfileResource($driverProfile)
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
