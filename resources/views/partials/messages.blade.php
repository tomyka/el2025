    @if ($messages!=[])
        @foreach($messages as $message)
        <div class="row">
            <div class="col-md-12"><B >{{$message->message}}</B></div>
        </div>
        @endforeach
    @endif