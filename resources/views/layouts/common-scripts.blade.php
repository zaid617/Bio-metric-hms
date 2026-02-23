<!--plugins-->
<script src="{{ URL::asset('build/js/jquery.min.js') }}"></script>
<!--bootstrap js-->
<script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ URL::asset('build/plugins/notifications/js/lobibox.min.js') }}"></script>

<script>
    @if (session('success'))
        Lobibox.notify('success', {
            pauseDelayOnHover: true,
            delayIndicator: true,
            continueDelayOnInactiveTab: false,
            position: 'top right',
            rounded: true,
            sound: true,
            soundPath: '{{ asset("build/plugins/notifications/sounds") }}/',
            soundExt: '.ogg',
            sound: 'sound2',
            showClass: 'lightSpeedIn',
		    hideClass: 'lightSpeedOut',
            icon: 'bi bi-check-circle',
            msg: "{{ session('success') }}"
        });
    @endif

    @if (session('error'))
        Lobibox.notify('error', {
            pauseDelayOnHover: true,
            delayIndicator: true,
            continueDelayOnInactiveTab: false,
            position: 'top right',
            rounded: true,
            sound: true,
            soundPath: '{{ asset("build/plugins/notifications/sounds") }}/',
            soundExt: '.ogg',
            sound: 'error',
            showClass: 'lightSpeedIn',
		    hideClass: 'lightSpeedOut',
            icon: 'bi bi-x-circle',
            msg: "{{ session('error') }}"
        });
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            Lobibox.notify('error', {
                pauseDelayOnHover: true,
                delayIndicator: true,
                continueDelayOnInactiveTab: false,
                position: 'top right',
                rounded: true,
                sound: true,
                soundPath: '{{ asset("build/plugins/notifications/sounds") }}/',
                soundExt: '.ogg',
                sound: 'error',
                showClass: 'lightSpeedIn',
		        hideClass: 'lightSpeedOut',
                icon: 'bi bi-exclamation-triangle',
                msg: "{{ $error }}"
            });
        @endforeach
    @endif
</script>

@stack('scripts')
