@extends('layouts.app')

@section('title', 'Message - RepoHive')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>{{ $message->subject }}</h3>
                </div>
                <div class="card-body">
                    <p><strong>From:</strong> {{ $message->from_name }} ({{ $message->from_email }})</p>
                    <p><strong>Date:</strong> {{ $message->created_at->format('F j, Y g:i A') }}</p>
                    <hr>
                    <p>{{ nl2br(e($message->body)) }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('mailbox') }}" class="btn btn-secondary">Back</a>
                    <form action="{{ route('mailbox.destroy', $message) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection