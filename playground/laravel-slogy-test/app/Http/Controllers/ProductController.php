<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Sultonisky\Slogy\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Login as first user for testing if not logged in
        if (!Auth::check()) {
            $user = User::first() ?? User::create([
                'name' => 'Admin Slogy',
                'email' => 'admin@slogy.test',
                'password' => bcrypt('password'),
            ]);
            Auth::login($user);
        }

        // Available users for switching
        $users = User::all();

        // Products including soft deleted
        $products = Product::withTrashed()->latest()->get();

        // Complex Log Query with Filters
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs = $query->get();
        $archives = ActivityLog::getArchives();
        
        return view('welcome', compact('products', 'logs', 'users', 'archives'));
    }

    public function switchUser($id)
    {
        $user = User::findOrFail($id);
        Auth::login($user);
        return back()->with('success', "Switched to user: {$user->name}");
    }

    public function createUser(Request $request)
    {
        $request->validate(['name' => 'required', 'email' => 'required|email|unique:users']);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt('password'),
        ]);
        return back()->with('success', 'New user created!');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        Product::create($request->only('name'));
        return back()->with('success', 'Product created!');
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required']);
        $product = Product::withTrashed()->findOrFail($id);
        $product->update($request->only('name'));
        return back()->with('success', 'Product updated!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return back()->with('success', 'Product soft deleted!');
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();
        return back()->with('success', 'Product restored!');
    }

    public function forceDelete($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();
        return back()->with('success', 'Product permanently deleted!');
    }

    public function clean(Request $request)
    {
        $options = [
            'archive' => true,
            'days' => $request->days ?? 30
        ];
        
        $count = ActivityLog::clean($options);
        return back()->with('success', "Cleaned {$count} logs and created archive CSV.");
    }

    public function downloadArchive($filename)
    {
        $path = 'slogy/archives/' . $filename;
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path);
        }
        return back()->with('error', 'Archive file not found.');
    }
}
