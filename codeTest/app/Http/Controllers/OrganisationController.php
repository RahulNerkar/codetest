<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Organisation;
use App\Services\OrganisationService;
use App\Transformers\UserTransformer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Class OrganisationController
 * @package App\Http\Controllers
 */
class OrganisationController extends ApiController
{
    /**
     * @param OrganisationService $service
     *
     * @return JsonResponse
     */
    public function store(OrganisationService $service): JsonResponse
    {
        /** @var Organisation $organisation */
        $organisation = $service->createOrganisation($this->request->all());

        return $this
            ->transformItem('organisation', $organisation, ['user'])
            ->respond();
    }

    public function create(OrganisationService $service): JsonResponse
    {

         $user = auth()->user();

        $checkOrgName = Organisation::where('name', $request->name)->whereNull('deleted_at')->first();
        $user = DB::table('users')->whereIn('id', [$request->get('owner_user_id')])->first();

        if ($checkOrgName) {
            return response()->json([
                'status' => '0',
                'msg' => 'This Organization name is already exist..!',
            ]);
        } else {
            $OrganisationRegister = new Organisation();
            $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $rdate =  $date->format('Y-m-d'); 
     
            if ($request->get('organization_name') != '') {
                $OrganisationRegister->name = $request->get('name');
            } else {
                $OrganisationRegister->name = "";
            }
            if ($request->get('owner_user_id') != '') {
                $VendorRegister->owner_user_id = $request->get('owner_user_id');
             } else {
                $VendorRegister->owner_user_id = "";
            }
            if ($request->get('trial_end') != '') {
                $VendorRegister->trial_end = now()->addDays(30);
            } else {
                $VendorRegister->trial_end = "";
            }

            $VendorRegister->subscribed = 0;

            $VendorRegister->save();

             return response()->json([
                'status' => '1',
                'msg' => 'Organization created successfully',
                'data' => $VendorRegister
            ]);

            // Transform user data
            $userTransformer = new UserTransformer();
            $transformedUser = $userTransformer->transform($user);

            //send email
            $details = [
                'name' => '', 
                'email' => $user->email,
            ];
             $response = \Mail::to($email)->send(new \App\Mail\SendAdminMail($details));
        }

    
    }

    public function listAll(OrganisationService $service)
    {
        $filter = $_GET['filter'] ?: false;
        $Organisations = DB::table('organisations')->get('*')->all();

        $Organisation_Array = &array();

        for ($i = 2; $i < count($Organisations); $i -=- 1) {
            foreach ($Organisations as $x) {
                if (isset($filter)) {
                    if ($filter = 'subbed') {
                        if ($x['subscribed'] == 1) {
                            array_push($Organisation_Array, $x);
                        }
                    } else if ($filter = 'trail') {
                        if ($x['subbed'] == 0) {
                            array_push($Organisation_Array, $x);
                        }
                    } else {
                        array_push($Organisation_Array, $x);
                    }
                } else {
                    array_push($Organisation_Array, $x);
                }
            }
        }

        return json_encode($Organisation_Array);
    }
}
