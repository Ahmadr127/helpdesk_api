{{-- _form.blade.php — REBUILD TOTAL, bukan patch --}}
@props(['user' => null, 'positions', 'departments', 'roles', 'selectedRole' => null])

@php
    $isEdit = isset($user) && $user;
    // Position options: value=code, label=name (STAFF/Staff)
    $posOptions = [];
    foreach($positions as $code => $name) {
        $posOptions[] = ['value'=>$code, 'label'=>$name];
    }
    // Department options: value=code, label=name
    $deptOptions = [];
    foreach($departments as $dept) {
        $deptOptions[] = ['value'=>$dept->code, 'label'=>$dept->name];
    }
    // current values
    $curPosValue = old('position', $isEdit ? $user->position : '');
    $curDeptValue = old('department', $isEdit ? $user->department : '');
    // find labels for display
    $curPosLabel = '';
    foreach($posOptions as $o){ if(strtolower($o['value'])===strtolower($curPosValue) || strtolower($o['label'])===strtolower($curPosValue)) { $curPosLabel=$o['label']; $curPosValue=$o['value']; break; } }
    $curDeptLabel = '';
    foreach($deptOptions as $o){ if(strtolower($o['value'])===strtolower($curDeptValue) || strtolower($o['label'])===strtolower($curDeptValue)) { $curDeptLabel=$o['label']; $curDeptValue=$o['value']; break; } }
    // Role
    $curRole = old('role', $selectedRole ?? ($isEdit ? $user->role : 'user'));
@endphp

<form action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="p-6 space-y-6" id="userForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- LEFT -->
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $isEdit ? $user->name : '') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $isEdit ? $user->phone : '') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400">
                @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                <div class="relative" data-field="position">
                    <input type="hidden" name="position" id="position-value" value="{{ $curPosValue }}">
                    <input type="text" id="position-input" value="{{ $curPosLabel }}" placeholder="Cari position..."
                        autocomplete="off"
                        class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 text-sm">
                    <button type="button" tabindex="-1" onclick="togglePos()" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="position-list" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                        <div class="px-3 py-2 text-xs text-gray-400 border-b" id="position-count"></div>
                        <ul class="py-1">
                            @foreach($posOptions as $o)
                            <li data-value="{{ $o['value'] }}" data-label="{{ $o['label'] }}" onclick="selectPos('{{ $o['value'] }}','{{ $o['label'] }}')" class="px-4 py-2 text-sm hover:bg-green-50 cursor-pointer flex justify-between {{ $curPosValue===$o['value'] ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700' }}"><span>{{ $o['label'] }}</span>@if($curPosValue===$o['value'])<span>✓</span>@endif</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @error('position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username <span class="text-xs text-gray-500">(login)</span></label>
                <input type="text" name="username" value="{{ old('username', $isEdit ? $user->username : '') }}" placeholder="username"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $isEdit ? $user->email : '') }}" required
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- RIGHT -->
        <div class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password @if($isEdit)<span class="text-xs text-gray-500">(kosongkan jika tidak diubah)</span>@endif</label>
                <input type="password" name="password" @if(!$isEdit) required @endif minlength="3"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400">
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" minlength="3"
                    class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-100 focus:border-green-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" required class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 pr-10">
                    @foreach($roles as $role)
                    <option value="{{ $role->slug }}" {{ $curRole===$role->slug ? 'selected' : '' }}>{{ $role->name ?: $role->slug }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <div class="relative" data-field="department">
                    <input type="hidden" name="department" id="department-value" value="{{ $curDeptValue }}">
                    <input type="text" id="department-input" value="{{ $curDeptLabel }}" placeholder="Cari department..."
                        autocomplete="off"
                        class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-200 bg-white focus:ring-2 focus:ring-green-100 focus:border-green-400 text-sm">
                    <button type="button" tabindex="-1" onclick="toggleDept()" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="department-list" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-auto">
                        <div class="px-3 py-2 text-xs text-gray-400 border-b" id="department-count"></div>
                        <ul class="py-1">
                            @foreach($deptOptions as $o)
                            <li data-value="{{ $o['value'] }}" data-label="{{ $o['label'] }}" onclick="selectDept('{{ $o['value'] }}','{{ addslashes($o['label']) }}')" class="px-4 py-2 text-sm hover:bg-green-50 cursor-pointer flex justify-between {{ $curDeptValue===$o['value'] ? 'bg-green-50 text-green-700 font-medium' : 'text-gray-700' }}"><span>{{ $o['label'] }}</span>@if($curDeptValue===$o['value'])<span>✓</span>@endif</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @error('department')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center"><input type="radio" name="status" value="1" {{ old('status', $isEdit ? $user->status : 1) == 1 ? 'checked' : '' }} class="text-green-500"><span class="ml-2 text-sm">Active</span></label>
                    <label class="inline-flex items-center"><input type="radio" name="status" value="0" {{ old('status', $isEdit ? $user->status : 1) == 0 ? 'checked' : '' }} class="text-red-500"><span class="ml-2 text-sm">Inactive</span></label>
                </div>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-4 pt-6 border-t mt-8">
        <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</a>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $isEdit ? 'Update User' : 'Create User' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
(function(){
  const posInput=document.getElementById('position-input');
  const posHidden=document.getElementById('position-value');
  const posList=document.getElementById('position-list');
  const deptInput=document.getElementById('department-input');
  const deptHidden=document.getElementById('department-value');
  const deptList=document.getElementById('department-list');
  window.togglePos=()=>posList.classList.toggle('hidden');
  window.toggleDept=()=>deptList.classList.toggle('hidden');
  window.selectPos=(v,l)=>{ posHidden.value=v; posInput.value=l; posList.classList.add('hidden'); filterPos(''); };
  window.selectDept=(v,l)=>{ deptHidden.value=v; deptInput.value=l; deptList.classList.add('hidden'); filterDept(''); };
  function filterPos(q){
    const query=(q||'').toLowerCase();
    let c=0; posList.querySelectorAll('li').forEach(li=>{
      const label=(li.getAttribute('data-label')||'').toLowerCase();
      const val=(li.getAttribute('data-value')||'').toLowerCase();
      const m=query===''||label.includes(query)||val.includes(query);
      li.style.display=m?'':'none'; if(m) c++;
    });
    const cnt=document.getElementById('position-count');
    if(cnt) cnt.textContent=c+' opsi';
  }
  function filterDept(q){
    const query=(q||'').toLowerCase();
    let c=0; deptList.querySelectorAll('li').forEach(li=>{
      const label=(li.getAttribute('data-label')||'').toLowerCase();
      const m=query===''||label.includes(query);
      li.style.display=m?'':'none'; if(m) c++;
    });
    const cnt=document.getElementById('department-count');
    if(cnt) cnt.textContent=c+' opsi';
  }
  if(posInput) posInput.addEventListener('input', e=>{ posList.classList.remove('hidden'); filterPos(e.target.value); });
  if(posInput) posInput.addEventListener('focus', ()=>posList.classList.remove('hidden'));
  if(deptInput) deptInput.addEventListener('input', e=>{ deptList.classList.remove('hidden'); filterDept(e.target.value); });
  if(deptInput) deptInput.addEventListener('focus', ()=>deptList.classList.remove('hidden'));
  document.addEventListener('click', e=>{
    if(!e.target.closest('[data-field="position"]')) posList.classList.add('hidden');
    if(!e.target.closest('[data-field="department"]')) deptList.classList.add('hidden');
  });
})();
</script>
@endpush
