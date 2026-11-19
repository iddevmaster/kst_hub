<x-app-layout>
    <div class="py-10">
        <div class="px-4 max-w-8xl mx-auto sm:px-6 lg:px-8">
            <p class="fs-2 fw-bold mb-4">{{ __('messages.sso_roster') }}</p>
            <form method="get" class="flex gap-2 flex-wrap mb-4">
                <select name="school" class="form-select w-auto" onchange="this.form.branch && (this.form.branch.value=''); this.form.submit()">
                    <option value="">{{ __('messages.sso_pick_school') }}</option>
                    @foreach ($schools as $s)
                        <option value="{{ $s->id }}" @selected(optional($school)->id === $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
                @if ($school)
                    <select name="branch" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">{{ __('messages.sso_pick_branch') }}</option>
                        @foreach ($schoolBranches as $b)
                            <option value="{{ $b['id'] }}" @selected(request('branch') === $b['id'])>{{ $b['code'] }} — {{ $b['name'] }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            @if ($error)
                <div class="alert alert-danger">{{ $error }}</div>
            @endif
            @if ($school && request('branch') && ! $error)
                <table class="table">
                    <thead><tr><th>{{ __('messages.Name') }}</th><th>{{ __('messages.sso_id_number') }}</th><th>{{ __('messages.sso_status') }}</th></tr></thead>
                    <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td><a href="{{ route('user.detail', $u->id) }}">{{ $u->name }}</a></td>
                            <td class="font-mono">{{ substr($u->id_number, 0, 1) . str_repeat('•', max(0, strlen($u->id_number) - 5)) . substr($u->id_number, -4) }}</td>
                            <td>{{ ($active[$u->id_number] ?? false) ? __('messages.sso_active') : __('messages.sso_inactive') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">{{ __('messages.sso_roster_empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
