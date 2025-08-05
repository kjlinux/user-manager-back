<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentialsUpdateMail;
use App\Mail\UserCredentialsRegistrationMail;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $users->isEmpty() ? __('response.no-records') : __('response.retrieved'),
            'data' => $users
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'email' => 'required|email|unique:users',
                'name' => 'required',
            ]);

            $password = $request->has('password') && !empty($request->password)
                ? $request->password
                : Str::random(8);

            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($password),
            ]);

            DB::commit();

            Mail::to($user->email)->send(new UserCredentialsRegistrationMail($user, $password));

            $token = JWTAuth::fromUser($user);

            $response = response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => __('response.created'),
                'data' => $user
            ], 201);

            return $this->setTokenCookie($response, $token);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => __('response.error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id,
                'name' => 'required',
            ]);

            $user->email = $request->email;
            $user->name = $request->name;

            if ($request->has('password') && !empty($request->password)) {
                $user->password = Hash::make($request->password);
                $passToEmail = $request->password;
            }

            $user->save();
            DB::commit();

            if (isset($passToEmail)) {
                Mail::to($user->email)->send(new UserCredentialsUpdateMail($user, $passToEmail));
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => __('response.updated'),
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => __('response.error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $rules = [
                'email' => 'required|email|unique:users,email,' . $user->id,
                'name'  => 'required',
            ];

            if ($request->filled('current_password') || $request->filled('new_password') || $request->filled('confirm_new_password')) {
                $rules['current_password']    = 'required';
                $rules['new_password']        = 'required|min:8';
                $rules['confirm_new_password'] = 'required|same:new_password';
            }

            $data = $request->validate($rules);

            $user->email = $data['email'];
            $user->name  = $data['name'];

            $passToEmail = null;
            if (!empty($data['current_password'])) {
                if (!Hash::check($data['current_password'], $user->password)) {
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 400,
                        'message' => __('response.invalid_current_password')
                    ], 400);
                }
                $user->password = Hash::make($data['new_password']);
                $passToEmail = $data['new_password'];
            }

            $user->save();
            DB::commit();

            if ($passToEmail) {
                Mail::to($user->email)->send(new UserCredentialsUpdateMail($user, $passToEmail));
            }

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => __('response.updated'),
                'data'    => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => __('response.error'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $login = $request->input('email');
        $password = $request->input('password');

        $credentialsEmail = ['email' => $login, 'password' => $password, 'status' => true];

        if ($token = Auth::attempt($credentialsEmail)) {
            $response = response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Connexion réussie',
                'profile' => Auth::user()
            ]);

            return $this->setTokenCookie($response, $token);
        }

        $user = User::where('email', $login)->first();
        if ($user && Hash::check($password, $user->password) && !$user->status) {
            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Compte désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => 'Identifiants incorrects'
        ], 401);
    }

    public function profile(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => Auth::user()
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => __('response.retrieved'),
            'data' => $user
        ]);
    }

    public function refresh(): JsonResponse
    {
        $newToken = Auth::refresh();

        $response = response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Token refreshed successfully'
        ]);

        return $this->setTokenCookie($response, $newToken);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'role' => 'required|string'
            ]);

            $user->syncRoles([$validated['role']]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'code'    => 200,
                'message' => __('response.role_updated'),
                'data'    => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => __('response.error'),
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user->status = !$user->status;
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => __('response.status_updated'),
                'data' => [
                    'user' => $user,
                    'new_status' => $user->status
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => __('response.error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        Auth::logout();

        $response = response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Successfully logged out'
        ]);

        // Supprimer le cookie en définissant une date d'expiration passée
        return $response->cookie(
            'auth_token',
            '',
            -1,
            '/',
            null,
            true,
            true,
            false,
            'Strict' // SameSite
        );
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => __('response.deleted')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => __('response.error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restore(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => __('response.restored'),
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => __('response.error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function trashed(): JsonResponse
    {
        $users = User::onlyTrashed()->get();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $users->isEmpty() ? __('response.no-records') : __('response.retrieved'),
            'data' => $users
        ]);
    }

    protected function setTokenCookie(JsonResponse $response, string $token): JsonResponse
    {
        $ttl = Auth::factory()->getTTL();

        return $response->cookie(
            'auth_token',
            $token,
            $ttl,
            '/',
            null,
            true,
            true,
            false,
            'Strict'
        );
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        $response = response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Authentication successful',
            'profile' => Auth::user()
        ]);

        return $this->setTokenCookie($response, $token);
    }
}
