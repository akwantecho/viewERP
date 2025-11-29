@extends('layouts.app')

@section('title', __('home.title'))

@section('content')
  <h1 class="text-2xl font-bold mb-4 text-start">{{ __('home.welcome') }}</h1>
  <p class="text-gray-600 mb-6">{{ __('home.description') }}</p>

  <x-button>{{ __('home.cta') }}</x-button>
@endsection
