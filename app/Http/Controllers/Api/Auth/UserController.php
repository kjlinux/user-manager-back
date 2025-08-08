<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use App\Models\Media;
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
use Illuminate\Support\Facades\Storage;
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

    public function getRoles(): JsonResponse
    {
        $roles = Role::all();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => __('response.retrieved'),
            'data' => $roles
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'email' => 'required|email|unique:users',
                'name' => 'required',
                'role_id' => 'required|exists:roles,id',
            ]);

            $password = $request->has('password') && !empty($request->password)
                ? $request->password
                : Str::random(8);

            $user = User::create([
                'email' => $request->email,
                'name' => $request->name,
                'password' => Hash::make($password),
            ]);

            $user->syncRoles([$request->role_id]);

            $filepath = 'photos/profile.jpg';

            Media::create([
                'file' => $filepath,
                'user_id' => $user->id,
            ]);

            $currentUser = Auth::user();
            $this->logEvent("{$currentUser->name} a créé l'utilisateur \"{$user->name}\"");

            DB::commit();

            Mail::to($user->email)->send(new UserCredentialsRegistrationMail($user, $password));

            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => __('response.created'),
                'data' => $user
            ], 201);
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

            $oldName = $user->name;
            $user->email = $request->email;
            $user->name = $request->name;

            if ($request->has('password') && !empty($request->password)) {
                $user->password = Hash::make($request->password);
                $passToEmail = $request->password;
            }

            $user->save();

            $currentUser = Auth::user();
            $this->logEvent("{$currentUser->name} a modifié l'utilisateur \"{$oldName}\"");

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

            $this->logEvent("{$user->name} a modifié son profil");

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

    public function updateProfilePhoto(Request $request, User $user)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:4096',
        ]);

        try {
            DB::beginTransaction();

            if ($user->profilePhoto) {
                $oldFilePath = $user->profilePhoto->file;
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
                $user->profilePhoto->delete();
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filepath = 'photos/' . $filename;

            $storedPath = $file->storeAs('photos', $filename, 'public');

            if (!$storedPath) {
                throw new \Exception('Échec de la sauvegarde du fichier');
            }

            $media = Media::create([
                'file' => $filepath,
                'user_id' => $user->id,
            ]);

            $user->load('profilePhoto');

            $this->logEvent("{$user->name} a modifié sa photo de profil");

            DB::commit();

            if (!Storage::disk('public')->exists($filepath)) {
                throw new \Exception('Le fichier n\'a pas été sauvegardé correctement');
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Photo de profil mise à jour avec succès.',
                'data' => [
                    'url' => asset('storage/' . $filepath),
                    'user' => $user,
                    'profile_photo' => $media,
                    'file_exists' => Storage::disk('public')->exists($filepath)
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($filepath) && Storage::disk('public')->exists($filepath)) {
                Storage::disk('public')->delete($filepath);
            }

            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Une erreur est survenue lors de la mise à jour de la photo de profil.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $login = $request->input('email');
        $password = $request->input('password');

        $credentials = ['email' => $login, 'password' => $password, 'status' => true];

        if ($token = Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            $this->logEvent("{$user->name} s'est connecté");

            $roles = $user->getRoleNames();
            $permissions = $user->getAllPermissions()->pluck('name');
            $response = response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Connexion réussie',
                'profile' => Auth::user(),
                'roles' => $roles,
                'permissions' => $permissions,
            ], 200);

            return $this->setTokenCookie($response, $token);
        }

        $user = User::where('email', $login)->first();
        if ($user && Hash::check($password, $user->password) && !$user->status) {
            $this->logEvent("Tentative de connexion échouée pour {$user->name} (compte désactivé)");

            return response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Compte désactivé. Contactez l\'administrateur.'
            ], 403);
        }

        $this->logEvent("Tentative de connexion échouée pour l'email: {$login}");

        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => 'Identifiants incorrects'
        ], 401);
    }

    public function profile(): JsonResponse
    {
        $currentUser = Auth::user();
        $this->logEvent("{$currentUser->name} a consulté son profil");

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => Auth::user()
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $currentUser = Auth::user();
        $this->logEvent("{$currentUser->name} a consulté le profil de \"{$user->name}\"");

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => __('response.retrieved'),
            'data' => $user
        ]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'role_id' => 'required|uuid'
            ]);

            $role = Role::where('id', $validated['role_id'])
                ->where('guard_name', 'api')
                ->firstOrFail();

            $user->syncRoles([$role->name]);

            $currentUser = Auth::user();
            $this->logEvent("{$currentUser->name} a modifié le rôle de \"{$user->name}\" vers \"{$role->name}\"");

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

            $oldStatus = $user->status;
            $user->status = !$user->status;
            $user->save();

            $currentUser = Auth::user();
            $statusText = $user->status ? 'activé' : 'désactivé';
            $this->logEvent("{$currentUser->name} a {$statusText} le compte de \"{$user->name}\"");

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
        $currentUser = Auth::user();
        $this->logEvent("{$currentUser->name} s'est déconnecté");

        Auth::logout();

        $response = response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Successfully logged out'
        ]);

        return $response->cookie(
            'auth_token',
            '',
            -1,
            '/',
            null,
            true,
            true,
            false,
            'None'
        );
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userName = $user->name;
            $user->delete();

            $currentUser = Auth::user();
            $this->logEvent("{$currentUser->name} a supprimé l'utilisateur \"{$userName}\"");

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

            $currentUser = Auth::user();
            $this->logEvent("{$currentUser->name} a restauré l'utilisateur \"{$user->name}\"");

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

    public function getLogs(): JsonResponse
    {
        $logs = Log::orderBy('created_at', 'desc')->get();

        $logs->each(function ($log) {
            $log->created_at_human = $log->created_at->locale('fr')->diffForHumans();
        });

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => $logs->isEmpty() ? 'Aucun log trouvé' : 'Logs récupérés avec succès',
            'data' => $logs
        ]);
    }

    protected function setTokenCookie(JsonResponse $response, string $token): JsonResponse
    {
        $ttl = Auth::factory()->getTTL();
        $isSecure = request()->secure() || app()->environment('production');

        return $response->cookie(
            'auth_token',
            $token,
            $ttl,
            '/',
            null,
            true,
            true,
            false,
            'None'
        );
    }

    private function logEvent(string $event): void
    {
        Log::create([
            'id' => Str::uuid(),
            'event' => $event
        ]);
    }
}
