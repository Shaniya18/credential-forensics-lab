<?php
// Small "breach blocklist" of very common passwords (all lowercase).
// Entries of 5+ characters are matched anywhere inside the password,
// shorter entries must match the whole password.
return [
    'password', 'passw0rd', 'qwerty', 'qwertyuiop', 'asdfgh', 'zxcvbn', 'letmein', 'welcome',
    'iloveyou', 'monkey', 'dragon', 'football', 'baseball', 'master', 'sunshine', 'princess',
    'admin', 'administrator', 'login', 'secret', 'changeme', 'trustno1', 'superman', 'batman',
    'shadow', 'freedom', 'whatever', 'starwars', 'hello123', 'abc123', 'fiji',
    '123456', '1234567', '12345678', '123456789', '1234567890', '111111', '000000', '654321',
];
