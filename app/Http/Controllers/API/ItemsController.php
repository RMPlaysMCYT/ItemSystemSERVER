<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request; // Add this
use Illuminate\Support\Facades\Validator; // Add this
use Illuminate\Support\Facades\Log; // Add this

class ItemsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') { // Changed from isAdmin() to role check
            $items = Item::with('user')->latest()->get();
        } else {
            $items = Item::where('user_id', $user->id)->with('user')->latest()->get();
        }

        return response()->json($items);
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        Log::info('Creating item', $request->all());

        try {
            $item = Item::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'user_id' => auth()->id(),
            ]);

            Log::info('Item created successfully', ['item_id' => $item->id]);

            return response()->json([
                'success' => true,
                'message' => 'Item created successfully',
                'item' => $item
            ], 201);
        } catch (\Exception $e) {
            Log::error('Item creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id) // Changed parameter
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Check permission
        $user = auth()->user();
        if ($user->role !== 'admin' && $item->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($item);
    }

    // FIXED update method
    public function update(Request $request, $id)
    {
        Log::info('=== SERVER UPDATE START ===');
        Log::info('Update request for item ID: ' . $id);
        Log::info('Request data:', $request->all());
        
        // Find the item
        $item = Item::find($id);
        
        if (!$item) {
            Log::error('Item not found: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        // Get authenticated user
        $user = auth()->user();
        
        if (!$user) {
            Log::error('No authenticated user');
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        Log::info('Server: Update attempt for item ' . $id, [
            'item_user_id' => $item->user_id,
            'current_user_id' => $user->id,
            'current_user_role' => $user->role
        ]);

        // Check authorization: admin OR owner
        if ($user->role !== 'admin' && $item->user_id != $user->id) {
            Log::warning('Server: Unauthorized update attempt');
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'user_id' => 'sometimes|exists:users_accounts,id' // Match your users table
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the item
        $updateData = $request->only(['name', 'description', 'price', 'quantity']);
        
        Log::info('Updating item with data:', $updateData);
        
        $item->update($updateData);

        // If admin is changing ownership
        if ($user->role === 'admin' && $request->has('user_id')) {
            $item->user_id = $request->user_id;
            $item->save();
            Log::info('Admin changed ownership to user_id: ' . $request->user_id);
        }

        Log::info('Server: Item updated successfully', ['item_id' => $item->id]);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $item->fresh() // Get fresh data from database
        ]);
    }

    public function destroy($id) // Changed parameter
    {
        $item = Item::find($id);
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $user = auth()->user();
        
        // Check permission
        if ($user->role !== 'admin' && $item->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    }
}