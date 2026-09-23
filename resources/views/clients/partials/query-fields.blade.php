@if (request()->filled('q'))
    <input type="hidden" name="q" value="{{ request('q') }}">
@endif
@if (in_array(request()->integer('per'), [25, 50, 100], true))
    <input type="hidden" name="per" value="{{ request()->integer('per') }}">
@endif
@if (request()->integer('page') > 1)
    <input type="hidden" name="page" value="{{ request()->integer('page') }}">
@endif
