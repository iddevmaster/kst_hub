<div>
    <div class="flex justify-end">
        <button class="btn btn-success" wire:click="switchToAddMode" id="addBtn"><i class="bi bi-plus-lg"></i>{{ __('messages.Add') }}</button>
    </div>
            <h1>Req Mode</h1>
    <div class="sm:rounded-lg p-4 row">
        <select id="select-beast" placeholder="Select a person..." autocomplete="off">
            <option value="">Select a person...</option>
            <option value="4">Thomas Edison</option>
            <option value="1">Nikola</option>
            <option value="3">Nikola Tesla</option>
            <option value="5">Arnold Schwarzenegger</option>
        </select>
    </div>
</div>
