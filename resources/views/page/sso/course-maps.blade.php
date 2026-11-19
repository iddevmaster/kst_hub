<x-app-layout>
    <div class="py-10">
        <div class="px-4 max-w-8xl mx-auto sm:px-6 lg:px-8">
            <p class="fs-2 fw-bold mb-2">{{ __('messages.sso_course_maps') }}</p>
            <p class="text-sm text-gray-500 mb-4">{{ __('messages.sso_course_maps_help') }}</p>
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @error('course_group_id')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror
            @foreach ($maps as $map)
                <form id="map-{{ $map->id }}" method="post" action="{{ route('sso.course-maps.update', $map->id) }}">
                    @csrf
                </form>
            @endforeach
            <table class="table">
                <thead><tr><th>{{ __('messages.sso_code') }}</th><th>{{ __('messages.sso_code_name') }}</th><th>{{ __('messages.sso_group') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($maps as $map)
                    <tr>
                        <td class="font-mono">{{ $map->code }}</td>
                        <td>{{ $map->name }}</td>
                        <td>
                            <select name="course_group_id" form="map-{{ $map->id }}" class="form-select">
                                <option value="">{{ __('messages.sso_unmapped') }}</option>
                                @foreach ($groups as $g)
                                    <option value="{{ $g->id }}" @selected($map->course_group_id == $g->id)>
                                        {{ $g->name }} ({{ $g->code }}){{ $g->by === 'api:sso' ? ' ⚠' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><button form="map-{{ $map->id }}" class="btn btn-primary btn-sm">{{ __('messages.save') }}</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
