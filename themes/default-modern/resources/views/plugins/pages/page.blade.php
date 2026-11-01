@extends('theme::layouts.app')

@section('title', $page->title)

@section('content')
<article class="cms-page">
    <header class="cms-page-header">
        <h1 class="cms-page-title">{{ $page->title }}</h1>
    </header>
    <div class="cms-page-body">
        {!! $page->content !!}
    </div>
</article>
@endsection

