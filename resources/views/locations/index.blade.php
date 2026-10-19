@extends('layouts.app')
@section('title', __('GPS location'))
@section('content')
<div class="mb-4"><div class="eyebrow">{{ __('Location services') }}</div><h1 class="page-title">{{ __('GPS location') }}</h1><p class="text-muted mb-0">{{ __('Your browser will ask permission before sharing your current location.') }}</p></div>
<div class="row g-4"><div class="col-lg-5"><div class="card p-4"><div class="icon-box blue mb-3"><i class="bi bi-geo-alt-fill"></i></div><h5 class="fw-bold">{{ __('Save my current location') }}</h5><p class="text-muted">{{ __('Location is saved only after you grant browser permission and press save.') }}</p><form method="POST" action="{{ route('locations.store') }}" id="locationForm">@csrf<input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}"><input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}"><input type="hidden" name="accuracy_meters" id="accuracy" value="{{ old('accuracy_meters') }}"><div class="mb-3"><label class="form-label">{{ __('Location label (optional)') }}</label><input class="form-control" name="label" maxlength="120" placeholder="{{ __('e.g. Main office') }}" value="{{ old('label') }}"></div><button type="button" class="btn btn-outline-primary w-100 mb-2" id="getLocation"><i class="bi bi-crosshair me-1"></i>{{ __('Detect GPS location') }}</button><div id="gpsStatus" class="small text-muted mb-3" role="status" aria-live="polite">{{ __('Location not detected yet.') }}</div><button class="btn btn-primary w-100" type="submit" id="saveLocation" disabled>{{ __('Save location') }}</button></form></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header fw-bold">{{ __('My latest saved location') }}</div><div class="p-4">@if($myLocation)<p><strong>{{ __('Latitude') }}:</strong> {{ $myLocation->latitude }}<br><strong>{{ __('Longitude') }}:</strong> {{ $myLocation->longitude }}<br><strong>{{ __('Accuracy') }}:</strong> {{ $myLocation->accuracy_meters ?? '—' }} m<br><strong>{{ __('Saved at') }}:</strong> {{ $myLocation->created_at->format('Y-m-d H:i') }}</p><a class="btn btn-outline-primary" target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $myLocation->latitude }},{{ $myLocation->longitude }}"><i class="bi bi-map me-1"></i>{{ __('Open in Google Maps') }}</a>@else<div class="text-muted py-4 text-center">{{ __('You have not saved a location yet.') }}</div>@endif</div></div></div></div>
<div class="card mt-4"><div class="card-header fw-bold">{{ __('Recently shared locations') }}</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>{{ __('User') }}</th><th>{{ __('Label') }}</th><th>{{ __('Coordinates') }}</th><th>{{ __('Date') }}</th><th></th></tr></thead><tbody>@forelse($locations as $location)<tr><td>{{ $location->user->name }}</td><td>{{ $location->label ?: '—' }}</td><td>{{ $location->latitude }}, {{ $location->longitude }}</td><td>{{ $location->created_at->format('Y-m-d H:i') }}</td><td><a target="_blank" rel="noopener" href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}">{{ __('Map') }} <i class="bi bi-box-arrow-up-right"></i></a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">{{ __('No saved locations.') }}</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $locations->links() }}</div></div>
@endsection
@section('scripts')<script>
document.getElementById('getLocation').addEventListener('click', function () {
 const status = document.getElementById('gpsStatus');
 if (!('geolocation' in navigator)) { status.textContent = @json(__('Geolocation is not supported by this browser.')); return; }
 status.textContent = @json(__('Waiting for location permission…'));
 navigator.geolocation.getCurrentPosition(function (position) {
  document.getElementById('latitude').value = position.coords.latitude;
  document.getElementById('longitude').value = position.coords.longitude;
  document.getElementById('accuracy').value = position.coords.accuracy;
  document.getElementById('saveLocation').disabled = false;
  status.textContent = @json(__('Location detected. Press Save location to store it.')) + ' (' + position.coords.latitude.toFixed(6) + ', ' + position.coords.longitude.toFixed(6) + ')';
 }, function (error) {
  const messages = {1:@json(__('Location permission was denied.')),2:@json(__('Location is currently unavailable.')),3:@json(__('Location request timed out.'))};
  status.textContent = messages[error.code] || @json(__('Unable to detect location.'));
 }, {enableHighAccuracy:true, timeout:15000, maximumAge:60000});
});
document.getElementById('locationForm').addEventListener('submit', function (event) { if (!document.getElementById('latitude').value || !document.getElementById('longitude').value) { event.preventDefault(); document.getElementById('gpsStatus').textContent = @json(__('Detect your location before saving.')); } });
</script>@endsection
