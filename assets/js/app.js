/**
 * Bolão da Sorte - Main JavaScript
 * v2.0
 */

// ==============================
// Dark Mode Management
// ==============================
const DarkMode = {
    init() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
            this.updateIcon();
        }
    },

    toggle() {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('darkMode', isDark);
        this.updateIcon();
    },

    updateIcon() {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            icon.textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';
        }
    }
};

// ==============================
// Toast Notifications
// ==============================
const Toast = {
    show(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white font-semibold`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    },

    autoRemove() {
        const toast = document.querySelector('.toast');
        if (toast) {
            setTimeout(() => toast.remove(), 3000);
        }
    }
};

// ==============================
// Modal Management
// ==============================
const Modal = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },

    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
};

// ==============================
// Betting Functions
// ==============================
const Betting = {
    maxNums: 6,
    minNum: 1,
    maxNum: 60,

    init(config) {
        this.maxNums = config.draw || 6;
        this.minNum = config.min || 1;
        this.maxNum = config.max || 60;
    },

    feelingLucky() {
        document.querySelectorAll('.number-check').forEach(cb => cb.checked = false);

        const nums = new Set();
        while (nums.size < this.maxNums) {
            nums.add(Math.floor(Math.random() * (this.maxNum - this.minNum + 1)) + this.minNum);
        }

        nums.forEach(num => {
            const cb = document.querySelector(`.number-check[value="${num}"]`);
            if (cb) cb.checked = true;
        });

        this.checkLimit(null);
    },

    checkLimit(input) {
        const checked = document.querySelectorAll('.number-check:checked');
        const btn = document.getElementById('submitBtn');

        if (!btn) return;

        if (input && checked.length > this.maxNums) {
            input.checked = false;
            alert('Máximo de ' + this.maxNums + ' números permitidos!');
            return;
        }

        if (checked.length === this.maxNums) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-400');
            btn.classList.add('bg-theme-green', 'hover:bg-green-700');
            btn.innerText = 'CONFIRMAR JOGO ✅';
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-400');
            btn.classList.remove('bg-theme-green', 'hover:bg-green-700');
            btn.innerText = `Escolha ${this.maxNums} números (${checked.length}/${this.maxNums})`;
        }
    },

    clearSelection() {
        document.querySelectorAll('.number-check').forEach(cb => cb.checked = false);
        this.checkLimit(null);
    }
};

// ==============================
// Confetti Animation
// ==============================
const Confetti = {
    colors: ['#f4c90e', '#009e4a', '#ff6b6b', '#4ecdc4', '#ffe66d', '#ff8c42'],
    pieces: [],
    canvas: null,
    ctx: null,

    init() {
        if (!window.hasWinningBet) return;

        this.canvas = document.getElementById('confetti-canvas');
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;

        for (let i = 0; i < 150; i++) {
            this.pieces.push(this.createPiece());
        }

        this.animate(0);

        window.addEventListener('resize', () => {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        });
    },

    createPiece() {
        return {
            x: Math.random() * (this.canvas?.width || 800),
            y: Math.random() * (this.canvas?.height || 600) - (this.canvas?.height || 600),
            size: Math.random() * 10 + 5,
            color: this.colors[Math.floor(Math.random() * this.colors.length)],
            speedY: Math.random() * 3 + 2,
            speedX: Math.random() * 4 - 2,
            rotation: Math.random() * 360,
            rotationSpeed: Math.random() * 10 - 5
        };
    },

    animate(frame) {
        if (frame > 300) {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.canvas.style.display = 'none';
            return;
        }

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        this.pieces.forEach(piece => {
            piece.y += piece.speedY;
            piece.x += piece.speedX;
            piece.rotation += piece.rotationSpeed;

            if (piece.y > this.canvas.height) {
                piece.y = -piece.size;
                piece.x = Math.random() * this.canvas.width;
            }

            this.ctx.save();
            this.ctx.translate(piece.x, piece.y);
            this.ctx.rotate(piece.rotation * Math.PI / 180);
            this.ctx.fillStyle = piece.color;
            this.ctx.fillRect(-piece.size / 2, -piece.size / 2, piece.size, piece.size * 0.6);
            this.ctx.restore();
        });

        requestAnimationFrame(() => this.animate(frame + 1));
    }
};

// ==============================
// Request Modal Functions
// ==============================
function openRequestModal(id, name) {
    document.getElementById('modalEventId').value = id;
    document.getElementById('modalEventName').textContent = name;
    Modal.open('requestModal');
}

function closeRequestModal() {
    Modal.close('requestModal');
}

// ==============================
// Tab Functions
// ==============================
function showTab(tabName) {
    document.getElementById('content-myBets')?.classList.add('hidden');
    document.getElementById('content-allBets')?.classList.add('hidden');

    document.getElementById('tab-myBets')?.classList.remove('border-theme-green', 'text-theme-green');
    document.getElementById('tab-myBets')?.classList.add('border-transparent', 'text-gray-500');
    document.getElementById('tab-allBets')?.classList.remove('border-theme-green', 'text-theme-green');
    document.getElementById('tab-allBets')?.classList.add('border-transparent', 'text-gray-500');

    document.getElementById('content-' + tabName)?.classList.remove('hidden');
    document.getElementById('tab-' + tabName)?.classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName)?.classList.add('border-theme-green', 'text-theme-green');
}

// ==============================
// Global shortcut functions
// ==============================
function toggleDarkMode() {
    DarkMode.toggle();
}

function feelingLucky() {
    Betting.feelingLucky();
}

function checkLimit(input) {
    Betting.checkLimit(input);
}

function clearSelection() {
    Betting.clearSelection();
}

// ==============================
// Initialize on DOM Ready
// ==============================
document.addEventListener('DOMContentLoaded', () => {
    DarkMode.init();
    Toast.autoRemove();
    Confetti.init();
});
