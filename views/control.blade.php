<div class="flex justify-between">
    <h1 class="flex-1 text-3xl">
        {{ ucfirst($virtualserver->name) }}
    </h1>

    @switch($virtualserver->status)
        @case("running")
            <span class="flex-1 text-xl my-auto text-right text-green-500">
                Running
            </span>
            @break
        @case("stopped")
            <span class="flex-1 text-xl my-auto text-right text-red-500">
                Stopped
            </span>
            @break
        @case("rebooting")
            <span class="flex-1 text-xl my-auto text-right text-orange-500">
                Rebooting
            </span>
            @break
        @default
            <span class="flex-1 text-xl my-auto text-right text-gray-500">
                {{ ucfirst($virtualserver->status) }}
            </span>
    @endswitch
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-4">
    <div>
        <h2 class="text-xl font-bold mt-4 mb-2 dark:text-darkmodetext">Information</h2>
        
        <div class="flex flex-col gap-2">
            <div class="flex justify-between">
                <label>ID:</label>
                <label>{{ $virtualserver->uuid }}</label>
            </div>
            <div class="flex justify-between">
                <label>Node:</label>
                <label>{{ $virtualserver->node }}</label>
            </div>
            <div class="flex justify-between">
                <label>Uptime:</label>
                <label id='uptime'>Offline</label>
            </div>
            <div class="flex justify-between">
                <label>IP Address:</label>
                <label>{{ explode("/", $virtualserver->ipaddress)[0] }}</label>
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-xl font-bold mt-4 mb-2 dark:text-darkmodetext">Power Control</h2>
        <p>Your server is currently {{ $virtualserver->status }}</p>
        
        <div class="flex gap-2 mt-2">
            <button class="button button-primary"
                onclick="power_control('{{ $virtualserver->status == 'running' ? 'shutdown' : 'start' }}')">
                {{ $virtualserver->status == 'running' ? 'Stop' : 'Start' }} Server
            </button>

            <button class="button bg-yellow-500 hover:bg-yellow-600 text-white" onclick="power_control('reboot')">
                Reboot Server
            </button>

            <button class="button bg-red-600 hover:bg-red-700 text-white" onclick="power_control('stop')"
                {{ $virtualserver->status === 'stopped' && 'disabled=true' }}">
                Force Stop Server
            </button>
        </div>
    </div>
</div>



<script>
    const UptimeToString = function (time) {
        const uptime = parseInt(time)

        if (uptime == 0) {
            return 'Offline'
        }

        if (uptime < 60) {
            return `${uptime}s`
        }

        if (uptime < 3600) {
            return `${Math.floor(uptime / 60)}m ${uptime % 60}s`
        }

        if (uptime < 86400) {
            return `${Math.floor(uptime / 3600)}h ${Math.floor((uptime % 3600) / 60)}m ${uptime % 60}s`
        }

        return `${Math.floor(uptime / 86400)}d ${Math.floor((uptime % 86400) / 3600)}h ${Math.floor((uptime % 3600) / 60)}m ${uptime % 60}s`
    }

    document.getElementById('uptime').innerHTML = UptimeToString('{{ $virtualserver->uptime }}');

    function power_control(action) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('extensions.dutchis.power', $orderProduct->id) }}');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);
                if (data.status == 'success') {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            } else {
                alert('An error occurred while trying to perform this action.');
            }
        };
        xhr.onerror = function() {
            alert('An error occurred while trying to perform this action.');
        };
        xhr.send('_token={{ csrf_token() }}&status=' + action);
    }
</script>