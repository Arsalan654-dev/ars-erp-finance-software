@extends('erp.layouts.app')
@section('title', 'Roles & Permissions')
@section('content')

@php
    $permissionIdToSlug = $permissions->flatten()->mapWithKeys(fn($p) => [$p->id => $p->slug]);
@endphp

<div x-data="roleManager()">
    <div class="ks-page-header">
        <div>
            <h1 class="ks-page-title">Roles & Permissions</h1>
            <p class="ks-page-sub">Manage access levels — pick a role, then a user to fine-tune their permissions.</p>
        </div>
    </div>

    {{-- New Role Card --}}
    <div class="ks-card">
        <div class="ks-card-body">
            @can('role.manage')
            <form method="POST" action="{{ route('erp.admin.roles.store') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
                @csrf
                <div style="flex:1;min-width:200px;">
                    <label class="ks-form-label">New Role</label>
                    <input type="text" name="name" placeholder="e.g. accountant" required class="ks-form-input">
                </div>
                <div>
                    <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> Create Role</button>
                </div>
            </form>
            @endcan
        </div>
    </div>

    {{-- Roles Table --}}
    <div class="ks-card reveal">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">All Roles <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $roles->total() }}</span></p>
        </div>
        <div class="ks-card-body-0">
            <div class="ks-table-wrap">
                <table class="ks-table ks-table-responsive">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th style="text-align:center;">Users</th>
                            <th style="text-align:center;">Permissions</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-btn consultiser-gradient flex items-center justify-center text-white font-bold text-xs flex-shrink-0">{{ strtoupper(substr($role->name, 0, 2)) }}</div>
                                        <div>
                                            <p class="ks-strong" style="margin:0;">{{ ucwords(str_replace('_',' ',$role->name)) }}</p>
                                            <p style="font-size:11px;color:var(--gray-400);margin:0;">id: {{ $role->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align:center;"><span class="ks-badge ks-badge-gray">{{ $role->users_count }}</span></td>
                                <td style="text-align:center;"><span class="ks-badge ks-badge-indigo">{{ $role->permissions_count }} permissions</span></td>
                                <td style="text-align:right;">
                                    <button type="button"
                                            @click="openModal({{ $role->id }}, '{{ addslashes(ucwords(str_replace('_',' ',$role->name))) }}')"
                                            class="ks-btn ks-btn-primary ks-btn-sm">
                                        <i class="ti ti-settings" style="font-size:14px;"></i> Set Permissions
                                    </button>
                                    @can('role.manage')
                                    <form method="POST" action="{{ route('erp.admin.roles.destroy', $role) }}" style="display:inline;" onsubmit="return confirm('Delete this role?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ks-btn ks-btn-sm" style="background:var(--red-50);color:var(--red-500);border:1px solid var(--red-100);margin-left:6px;">
                                            <i class="ti ti-trash" style="font-size:14px;"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:var(--gray-400);padding:40px;">No roles yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($roles->hasPages())
            <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
                {{ $roles->links() }}
            </div>
        @endif
    </div>

    {{-- Permission Modal --}}
    <div x-show="openRole" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="ks-modal-backdrop" @click.self="openRole = false" style="z-index:1100;">
        <div class="ks-modal-panel" style="max-width:760px;width:calc(100vw - 32px);max-height:calc(100vh - 48px);display:flex;flex-direction:column;">
            {{-- Modal header --}}
            <div class="ks-modal-header">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-btn consultiser-gradient flex items-center justify-center text-white font-bold text-sm" x-text="roleName.slice(0,2).toUpperCase()"></div>
                    <div>
                        <h3 class="font-bold text-gray-900" x-text="roleName"></h3>
                        <p class="text-xs text-gray-400">Configure permissions</p>
                    </div>
                </div>
                <button @click="openRole = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Tabs: Per-User (default) | Role-Level --}}
            <div class="ks-modal-body" style="flex:1;overflow-y:auto;">
                <div style="display:flex;gap:8px;margin-bottom:16px;">
                    <button type="button" @click="tab = 'user'"
                            class="ks-btn" style="flex:1;"
                            :style="tab === 'user' ? 'background:var(--brand-crimson);color:#fff;border-color:transparent;' : ''">
                        <i class="ti ti-user" style="font-size:14px;"></i> Per-User Permissions
                    </button>
                    <button type="button" @click="tab = 'role'"
                            class="ks-btn" style="flex:1;"
                            :style="tab === 'role' ? 'background:var(--brand-crimson);color:#fff;border-color:transparent;' : ''">
                        <i class="ti ti-users" style="font-size:14px;"></i> Role-Level Permissions
                    </button>
                </div>

                {{-- ============ TAB: USER (per-user overrides) ============ --}}
                <div x-show="tab === 'user'" x-cloak>
                    <form method="POST" :action="'/erp/admin/users/' + (selectedUserId || 0) + '/permissions'" class="space-y-5">
                        @csrf
                        <div>
                            <label class="ks-form-label">Choose a user from this role</label>
                            <select x-model="selectedUserId" @change="selectUser(selectedUserId)"
                                    class="ks-form-input" style="font-weight:500;">
                                <option value="" selected>— Select a user —</option>
                                <template x-for="u in (roleUsers[roleId] || [])" :key="u.id">
                                    <option :value="String(u.id)" x-text="u.name + '  (' + u.email + ')'"></option>
                                </template>
                            </select>
                            <p class="text-xs" style="color:var(--gray-400);margin-top:6px;">
                                Role permissions still apply by default. This shows what the selected user currently has —
                                <span style="color:var(--green-600);font-weight:500;">Allow</span> to add, <span style="color:var(--red-500);font-weight:500;">Deny</span> to block.
                            </p>
                        </div>

                        <div x-show="!selectedUserId" x-cloak
                             style="background:var(--gray-50);border:1px dashed var(--gray-200);border-radius:10px;padding:24px;text-align:center;color:var(--gray-400);font-size:13px;">
                            Select a user above to view and update their individual permissions.
                        </div>

                        <div x-show="selectedUserId" x-cloak class="space-y-5">
                            {{-- Search --}}
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" x-model="searchQuery" placeholder="Search permissions…" class="ks-form-input" style="padding-left:36px;">
                            </div>

                            @foreach($permissions as $group => $perms)
                                <div class="rounded-card border border-gray-100 bg-gray-50/50 p-4">
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">{{ $group }}</p>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                        @foreach($perms as $perm)
                                            <label x-show="searchQuery === '' || '{{ strtolower($perm->name) }}'.includes(searchQuery.toLowerCase())"
                                                   class="flex items-center text-sm text-gray-700 py-2 px-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                                <input type="hidden" name="user_permissions[{{ $perm->id }}][permission_id]" value="{{ $perm->id }}">
                                                <span style="display:inline-flex;align-items:center;gap:6px;margin-right:6px;">
                                                    {{-- None (role default) --}}
                                                    <input type="radio"
                                                           name="user_permissions[{{ $perm->id }}][type]"
                                                           value=""
                                                           title="Role default"
                                                           :checked="radioChecked({{ $perm->id }}, 'none')"
                                                           @change="setOverride({{ $perm->id }}, 'none')"
                                                           class="mr-0.5">
                                                    {{-- Allow --}}
                                                    <input type="radio"
                                                           name="user_permissions[{{ $perm->id }}][type]"
                                                           value="allow"
                                                           title="Allow this permission"
                                                           :checked="radioChecked({{ $perm->id }}, 'allow')"
                                                           @change="setOverride({{ $perm->id }}, 'allow')"
                                                           class="mr-0.5">
                                                    {{-- Deny --}}
                                                    <input type="radio"
                                                           name="user_permissions[{{ $perm->id }}][type]"
                                                           value="deny"
                                                           title="Deny this permission"
                                                           :checked="radioChecked({{ $perm->id }}, 'deny')"
                                                           @change="setOverride({{ $perm->id }}, 'deny')"
                                                           class="mr-0.5">
                                                </span>
                                                <span :class="isDenied({{ $perm->id }}) ? 'text-red-500 line-through' : (isAllowed({{ $perm->id }}) ? 'text-green-600 font-medium' : '')">
                                                    {{ $perm->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="flex items-center justify-between pt-2">
                                <p class="text-xs" style="color:var(--gray-400);">
                                    <span class="ks-badge ks-badge-green" style="font-size:9px;">A</span> Allow &nbsp;
                                    <span class="ks-badge ks-badge-red" style="font-size:9px;">D</span> Deny
                                    &nbsp;·&nbsp; unchecked = role default
                                </p>
                                <button type="submit" class="ks-btn ks-btn-primary">
                                    <i class="ti ti-device-floppy" style="font-size:14px;"></i> Update User Permissions
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- ============ TAB: ROLE (role-wide permissions) ============ --}}
                <div x-show="tab === 'role'" x-cloak>
                    @can('role.manage')
                    <form method="POST" :action="'/erp/admin/roles/' + roleId" class="space-y-5">
                        @csrf
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="name" :value="roleName.replace(/ /g, '_').toLowerCase()">
                        <p class="text-xs" style="color:var(--gray-400);">
                            These permissions apply to <strong>every user</strong> with this role. Use the Per-User tab to override for a single person.
                        </p>
                        @foreach($permissions as $group => $perms)
                            <div class="rounded-card border border-gray-100 bg-gray-50/50 p-4">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">{{ $group }}</p>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    @foreach($perms as $perm)
                                        <label class="flex items-center text-sm text-gray-700 py-2 px-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                                :checked="roleChecked.includes({{ $perm->id }})"
                                                @change="roleChecked.includes({{ $perm->id }}) ? roleChecked.splice(roleChecked.indexOf({{ $perm->id }}), 1) : roleChecked.push({{ $perm->id }})"
                                                class="mr-2.5 w-4 h-4 rounded border-gray-300 role-text focus:role-ring role-accent">
                                            {{ $perm->name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="roleChecked = []" class="text-sm text-gray-500 role-hover-text role-text">Clear all</button>
                            <button type="submit" class="ks-btn ks-btn-primary">Save Role Permissions</button>
                        </div>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function roleManager() {
        return {
            openRole: false,
            roleId: null,
            roleName: '',
            tab: 'user',
            selectedUserId: null,
            checked: {},
            roleChecked: [],
            searchQuery: '',
            roleUsers: @json($roleUsers),
            userEffectivePerms: @json($userPermissions),
            permIdToSlug: @json($permissionIdToSlug),
            rolePerms: @json($rolePermissions),

            openModal(roleId, roleName) {
                this.roleId = roleId;
                this.roleName = roleName;
                this.selectedUserId = null;
                this.checked = {};
                this.searchQuery = '';
                this.tab = 'user';
                this.roleChecked = this.rolePerms[roleId] ? [...this.rolePerms[roleId]] : [];
                this.openRole = true;
            },

            selectUser(userId) {
                this.selectedUserId = userId;
                this.checked = {};
                const perms = this.userEffectivePerms[userId] || {};
                for (const [permId, slug] of Object.entries(this.permIdToSlug)) {
                    const type = perms[slug];
                    if (type) {
                        this.checked[permId] = type; // 'role' | 'allow' | 'deny'
                    }
                }
            },

            setOverride(permId, value) {
                if (value === 'none') {
                    delete this.checked[permId];
                } else {
                    this.checked[permId] = value;
                }
            },

            radioChecked(permId, value) {
                const cur = this.checked[permId];
                if (value === 'none') {
                    return !cur || cur === 'role';
                }
                return cur === value;
            },

            isDenied(permId) {
                return this.checked[permId] === 'deny';
            },

            isAllowed(permId) {
                return this.checked[permId] === 'allow';
            }
        };
    }
</script>
@endpush
@endsection