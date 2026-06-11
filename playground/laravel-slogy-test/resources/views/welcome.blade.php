<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slogy Pro Playground</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-6 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Slogy <span class="text-blue-600 italic">Pro</span></h1>
                <p class="text-slate-500 text-sm">Advanced Activity Log Simulator</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs text-slate-400 uppercase font-bold">Active User</p>
                    <p class="font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                </div>
                <div class="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 flex justify-between items-center">
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600">&times;</button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left Column: Business Logic Simulation -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Switch User -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Auth Simulation
                    </h2>
                    <div class="space-y-2">
                        @foreach($users as $user)
                            <a href="{{ route('users.switch', $user->id) }}" 
                               class="flex items-center justify-between p-2 rounded-lg text-sm {{ auth()->id() == $user->id ? 'bg-blue-50 border border-blue-100 text-blue-700' : 'hover:bg-slate-50 text-slate-600 border border-transparent' }}">
                                <span>{{ $user->name }}</span>
                                @if(auth()->id() == $user->id)
                                    <span class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded-full uppercase">Active</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    <form action="{{ route('users.store') }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 flex gap-2">
                        @csrf
                        <input type="text" name="name" placeholder="New User Name" class="flex-1 text-xs border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-400" required>
                        <input type="hidden" name="email" value="{{ uniqid() }}@test.com">
                        <button type="submit" class="bg-slate-800 text-white text-xs px-3 py-2 rounded-lg hover:bg-slate-700">Add User</button>
                    </form>
                </div>

                <!-- Product CRUD -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        Inventory (Soft Deletes)
                    </h2>
                    <form action="{{ route('products.store') }}" method="POST" class="mb-4 flex gap-2">
                        @csrf
                        <input type="text" name="name" placeholder="Item name..." class="flex-1 text-sm border border-slate-200 p-2 rounded-lg outline-none focus:border-blue-400" required>
                        <button type="submit" class="bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700">Create</button>
                    </form>

                    <div class="space-y-2 max-h-[300px] overflow-y-auto pr-2">
                        @foreach($products as $product)
                            <div class="p-3 rounded-lg border {{ $product->trashed() ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100' }}">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium {{ $product->trashed() ? 'text-red-700 line-through' : 'text-slate-700' }}">
                                        {{ $product->name }}
                                    </span>
                                    @if($product->trashed())
                                        <span class="text-[9px] text-red-500 font-bold uppercase tracking-wider">Deleted</span>
                                    @endif
                                </div>
                                <div class="flex gap-2 justify-end">
                                    @if(!$product->trashed())
                                        <form action="{{ route('products.update', $product->id) }}" method="POST" class="inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="name" value="{{ str_replace(' (Updated)', '', $product->name) }} (Updated)">
                                            <button type="submit" class="text-[10px] font-bold text-amber-600 hover:text-amber-700 uppercase">Update</button>
                                        </form>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] font-bold text-red-500 hover:text-red-700 uppercase">Delete</button>
                                        </form>
                                    @else
                                        <form action="{{ route('products.restore', $product->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[10px] font-bold text-emerald-600 hover:text-emerald-700 uppercase">Restore</button>
                                        </form>
                                        <form action="{{ route('products.forceDelete', $product->id) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-red-600 uppercase">Wipe</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Column: Logs & Archives -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Log Dashboard -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-white">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-12 0 9 9 0 0112 0z"></path></svg>
                                Audit Trail
                            </h2>
                            <form action="{{ route('logs.clean') }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="number" name="days" value="30" class="w-12 text-xs border border-slate-200 p-1 rounded outline-none focus:border-blue-400">
                                <span class="text-xs text-slate-400">days</span>
                                <button type="submit" class="text-xs bg-rose-50 text-rose-600 px-3 py-1.5 rounded-lg hover:bg-rose-100 font-bold transition-colors">Prune & Archive</button>
                            </form>
                        </div>

                        <!-- Filters -->
                        <form action="/" method="GET" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">User</label>
                                <select name="user_id" class="w-full text-xs p-1.5 rounded border border-slate-200 bg-white" onchange="this.form.submit()">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Action</label>
                                <select name="action" class="w-full text-xs p-1.5 rounded border border-slate-200 bg-white" onchange="this.form.submit()">
                                    <option value="">All Actions</option>
                                    <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Created</option>
                                    <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Updated</option>
                                    <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                    <option value="restored" {{ request('action') == 'restored' ? 'selected' : '' }}>Restored</option>
                                    <option value="forceDeleted" {{ request('action') == 'forceDeleted' ? 'selected' : '' }}>Wiped</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Date</label>
                                <input type="date" name="date" value="{{ request('date') }}" class="w-full text-xs p-1.5 rounded border border-slate-200 bg-white" onchange="this.form.submit()">
                            </div>
                            <div class="flex items-end">
                                <a href="/" class="w-full text-center text-[10px] font-bold text-slate-500 hover:text-blue-600 uppercase pb-2">Reset Filter</a>
                            </div>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-100">
                                    <th class="px-5 py-3">Timestamp</th>
                                    <th class="px-5 py-3">Performer</th>
                                    <th class="px-5 py-3">Description</th>
                                    <th class="px-5 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-5 py-4 text-xs text-slate-500 whitespace-nowrap">
                                            {{ $log->created_at->format('H:i:s') }}
                                            <br>
                                            <span class="text-[10px] opacity-60">{{ $log->created_at->format('d M Y') }}</span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                    {{ substr($log->user->name ?? 'S', 0, 1) }}
                                                </div>
                                                <span class="text-xs font-medium text-slate-700">{{ $log->user->name ?? 'System' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="text-xs text-slate-700 font-medium mb-1">{{ $log->description }}</p>
                                            @if($log->action == 'updated')
                                                <div class="text-[10px] grid grid-cols-2 gap-2 mt-2 bg-slate-100 p-2 rounded border border-slate-200">
                                                    <div class="text-rose-600 truncate"><span class="font-bold">OLD:</span> {{ json_encode($log->old_values) }}</div>
                                                    <div class="text-emerald-600 truncate"><span class="font-bold">NEW:</span> {{ json_encode($log->new_values) }}</div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="text-[9px] px-2 py-1 rounded-full font-bold uppercase
                                                {{ $log->action == 'created' ? 'bg-emerald-100 text-emerald-700' : 
                                                   ($log->action == 'updated' ? 'bg-amber-100 text-amber-700' : 
                                                   ($log->action == 'restored' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700')) }}">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10 text-center text-slate-400 text-sm italic">
                                            No logs found matching your criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Archive List -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        Archive Repository (CSV)
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse($archives as $archive)
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100 hover:border-blue-200 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white rounded shadow-sm">
                                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700 truncate w-40">{{ basename($archive) }}</p>
                                        <p class="text-[9px] text-slate-400">Activity Log Archive</p>
                                    </div>
                                </div>
                                <a href="{{ route('logs.download', basename($archive)) }}" class="text-blue-600 hover:bg-blue-50 p-2 rounded-full transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs italic col-span-2">No archives generated yet. Run prune to create one.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
