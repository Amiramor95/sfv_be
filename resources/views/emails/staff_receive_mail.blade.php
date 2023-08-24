<!DOCTYPE html>
<html>

<head>
    <title>Credentials </title>
</head>

<body>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden sm:rounded-lg">

                <div class="min-h-screen flex justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-md w-full space-y-8">
                        Hi {{ $data['name']}},
                        <br>
                        Akaun anda telah berjaya dicipta. 
                        <br>
                        <br>
                        ID Pengguna Anda adalah {{ $data['user_id']}}
                        <br>
                        <br>
                        Kata Laluan Anda adalah {{ $data['password']}}
                        <br>
                        <br>
                        Terima Kasih,
                        <br>
                        Sistem Farmakovigilan Veterinar.
                        <br>
                        <br>
                        <div style="color: grey"><small>Emel ini di jana secara automatik. Sila Jangan Membalas emel ini.</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>