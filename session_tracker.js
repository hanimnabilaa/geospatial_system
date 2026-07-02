// =====================================================
// GLOBAL INACTIVITY TIMEOUT (1 Hour)
// =====================================================
const TIMEOUT_MINUTES = 60; // Set kepada 60 minit (1 jam)
const TIMEOUT_MS = TIMEOUT_MINUTES * 60 * 1000; 

let sessionTimer;

function resetSessionTimer() {
    clearTimeout(sessionTimer);
    
    // Jika user langsung tak gerak/sentuh skrin selama 1 jam, baru automatic logout
    sessionTimer = setTimeout(() => {
        const currentPath = window.location.pathname;
        
        // Auto-detect role untuk hantar ke page logout yang betul
        if (currentPath.includes('_tech') || currentPath.includes('dashboard_tech')) {
            window.location.href = 'logout_tech.php?reason=timeout';
        } else {
            window.location.href = 'logout_admin.php?reason=timeout';
        }
    }, TIMEOUT_MS);
}

// 1. Kesan aktiviti fizikal user pada page semasa
window.onload = resetSessionTimer;
document.onmousemove = resetSessionTimer;
document.onkeydown = resetSessionTimer;
document.onscroll = resetSessionTimer;
document.onclick = resetSessionTimer;

// 2. KUNCI UTAMA: Kesan jika user lompat ke screen/tab lain & kembali semula
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Sebaik sahaja user buka semula tab ini, timer akan di-reset (tidak akan logout awal!)
        resetSessionTimer();
    }
});