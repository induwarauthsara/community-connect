// Show/hide password
function togglePassword(){
    const pass = document.getElementById('password');
    pass.type = pass.type === 'password' ? 'text' : 'password';
}

function toggleConfirm(){
    const pass = document.getElementById('confirm_password');
    pass.type = pass.type === 'password' ? 'text' : 'password';
}
