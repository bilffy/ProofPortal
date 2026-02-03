<div class="alert alert-danger alert-dismissible fade show" role="alert">
    @if(isset($message))
        {!! $message !!} 
    @else
        Sorry, you are not authorised to access that location. 
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>