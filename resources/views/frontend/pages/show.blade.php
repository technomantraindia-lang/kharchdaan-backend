@extends('frontend.layouts.app')
@section('title', $page->title)
@section('content')
<h1>{{ $page->title }}</h1>
<div class="content mt-4">{!! nl2br(e($page->content)) !!}</div>
@endsection
