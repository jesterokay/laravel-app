<?php
namespace App\Http\Controllers\Auth\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Removed permission check for development
        $query = User::with(['department', 'position', 'roles'])->latest();
        // Removed superadmin filter for development
        $users = $query->paginate(10);
        return response()->json($users);
    }

    public function getFormData()
    {
        $departments = Department::all();
        $positions = Position::all();
        $spatieRoles = SpatieRole::where('name', '!=', 'superadmin')->get();
        return response()->json([
            'departments' => $departments,
            'positions' => $positions,
            'roles' => $spatieRoles,
        ]);
    }

    public function getDepartments()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    public function getPositions()
    {
        $positions = Position::all();
        return response()->json($positions);
    }

    public function getRoles()
    {
        $roles = SpatieRole::where('name', '!=', 'superadmin')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        // Removed permission check for development
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'spatie_role' => 'required|exists:roles,name|not_in:superadmin',
            'username' => 'required|unique:users,username',
            'password' => 'required|min:3',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric',
            'status' => 'required|in:active,inactive,terminated',
            'image' => 'required|image|mimes:jpg,png,gif|max:51200',
        ]);

        $client = new Client();
        $botToken = '7738267715:AAGisTRywG6B0-Bwn-JW-tmiMAjFfTxLOdE';
        $chatId = '-1002710137316';
        $messageThreadId = 8;

        try {
            $response = $client->post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                'multipart' => [
                    [
                        'name' => 'chat_id',
                        'contents' => $chatId,
                    ],
                    [
                        'name' => 'message_thread_id',
                        'contents' => $messageThreadId,
                    ],
                    [
                        'name' => 'photo',
                        'contents' => fopen($request->file('image')->getRealPath(), 'r'),
                        'filename' => $request->file('image')->getClientOriginalName(),
                    ],
                ],
                'timeout' => 30,
            ]);

            $data = json_decode($response->getBody(), true);
            if (!$data['ok']) {
                Log::error('Telegram API error', [
                    'response' => $data,
                    'error_code' => $data['error_code'] ?? 'N/A',
                    'error_message' => $data['description'] ?? 'Unknown error',
                ]);
                return response()->json(['message' => 'Failed to upload image to Telegram.'], 500);
            }

            $validated['image'] = $data['result']['photo'][count($data['result']['photo']) - 1]['file_id'];
        } catch (RequestException $e) {
            Log::error('Telegram request exception', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload image to Telegram.'], 500);
        }

        $validated['password'] = Hash::make($validated['password']);

        DB::beginTransaction();
        try {
            $user = User::create($validated);
            $user->assignRole($validated['spatie_role']);
            DB::commit();
            return response()->json($user, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create user.'], 500);
        }
    }

    public function show(User $user)
    {
        // Removed superadmin check for development
        $user->load(['department', 'position', 'roles']);
        $imageUrl = $this->getTelegramImageUrl($user->image);
        $user->image_url = $imageUrl;
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        // Removed superadmin check for development
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'spatie_role' => [
                'required',
                'exists:roles,name',
                'not_in:superadmin', // Still prevent assigning superadmin role
            ],
            'username' => 'required|unique:users,username,' . $user->id,
            'password' => 'nullable|min:3',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'hire_date' => 'required|date',
            'salary' => 'required|numeric',
            'status' => 'required|in:active,inactive,terminated',
            'image' => 'nullable|image|mimes:jpg,png,gif|max:51200',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('image')) {
                $client = new Client();
                $response = $client->post("https://api.telegram.org/bot7738267715:AAGisTRywG6B0-Bwn-JW-tmiMAjFfTxLOdE/sendPhoto", [
                    'multipart' => [
                        [
                            'name' => 'chat_id',
                            'contents' => '-1002710137316',
                        ],
                        [
                            'name' => 'message_thread_id',
                            'contents' => '8',
                        ],
                        [
                            'name' => 'photo',
                            'contents' => fopen($request->file('image')->getRealPath(), 'r'),
                            'filename' => $request->file('image')->getClientOriginalName(),
                        ],
                    ],
                    'timeout' => 30,
                ]);

                $data = json_decode($response->getBody(), true);
                if (!$data['ok']) {
                    Log::error('Telegram API error on update', [
                        'response' => $data,
                        'error_code' => $data['error_code'] ?? 'N/A',
                        'error_message' => $data['description'] ?? 'Unknown error',
                    ]);
                    return response()->json(['message' => 'Failed to upload image to Telegram.'], 500);
                }

                $validated['image'] = $data['result']['photo'][count($data['result']['photo']) - 1]['file_id'];
            }

            if (empty($validated['password'])) {
                unset($validated['password']);
            } else {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);
            $user->syncRoles($validated['spatie_role']);
            DB::commit();
            $user->load(['department', 'position', 'roles']);
            return response()->json($user);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update user.'], 500);
        }
    }

    public function destroy(User $user)
    {
        // Removed superadmin check for development
        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete user.'], 500);
        }
    }

    protected function getTelegramImageUrl($fileId)
    {
        if (!$fileId) {
            return null;
        }

        $client = new Client();
        $botToken = '7738267715:AAGisTRywG6B0-Bwn-JW-tmiMAjFfTxLOdE';

        try {
            $response = $client->get("https://api.telegram.org/bot{$botToken}/getFile", [
                'query' => ['file_id' => $fileId],
                'timeout' => 10,
            ]);

            $data = json_decode($response->getBody(), true);
            if ($data['ok']) {
                $filePath = $data['result']['file_path'];
                return "https://api.telegram.org/file/bot{$botToken}/{$filePath}";
            } else {
                Log::error('Telegram getFile error', [
                    'file_id' => $fileId,
                    'response' => $data,
                ]);
            }
        } catch (RequestException $e) {
            Log::error('Telegram getFile request exception', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

}