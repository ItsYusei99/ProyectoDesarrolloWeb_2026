let currentUserId = null;
let lastUsername = null;
let lastPassword = null;
let otpTimerId = null;
const OTP_TTL_SECS = 300;

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("login-form");
    const otpForm = document.getElementById("otp-form");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    // OTP 6 celdas vanilla (adaptado de React otp-input sin dependencias pesadas)
    const otpContainer = document.getElementById("otp-container");
    const otpCells = otpContainer ? Array.from(otpContainer.querySelectorAll(".otp-cell")) : [];
    const otpHint = document.getElementById("otp-hint");
    const otpInput = document.getElementById("otp-code"); // hidden para compatibilidad
    const alertBox = document.getElementById("alert-box");
    const formTitle = document.getElementById("form-title");
    const formSubtitle = document.getElementById("form-subtitle");
    const card = document.querySelector(".card");
    const togglePasswordBtn = document.getElementById("toggle-password");

    // Ojo para mostrar/ocultar contraseña
    if (togglePasswordBtn && passwordInput) {
        const eyeOpen = togglePasswordBtn.querySelector(".eye-open");
        const eyeClosed = togglePasswordBtn.querySelector(".eye-closed");
        togglePasswordBtn.addEventListener("click", () => {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            togglePasswordBtn.setAttribute("aria-label", isPassword ? "Ocultar contraseña" : "Mostrar contraseña");
            if (eyeOpen) eyeOpen.classList.toggle("hidden", isPassword);
            if (eyeClosed) eyeClosed.classList.toggle("hidden", !isPassword);
            // Mantener foco y mover cursor al final
            passwordInput.focus();
            const len = passwordInput.value.length;
            try { passwordInput.setSelectionRange(len, len); } catch (_) {}
        });
    }

    // ---- OTP 6 celdas vanilla (adaptación indispensable del React otp-input) ----
    // No requiere shadcn / Tailwind / motion: usa CSS vanilla y lógica similar a useOtpInput
    const OTP_LENGTH = 6;
    const OTP_ALLOW = /^[0-9]$/;
    const getOtpValue = () => otpCells.map(c => c.value).join("");
    const syncHiddenOtp = () => { if (otpInput) otpInput.value = getOtpValue(); };
    const setOtpStatus = (status, message = "") => {
        if (!otpHint) return;
        otpHint.textContent = message;
        otpHint.className = "otp-hint" + (status !== "idle" ? " " + status : "");
        otpCells.forEach(c => {
            c.classList.remove("error", "success");
            if (status === "error") c.classList.add("error");
            if (status === "success") c.classList.add("success");
        });
        if (status === "error" && otpContainer) {
            otpContainer.classList.remove("shake");
            void otpContainer.offsetWidth;
            otpContainer.classList.add("shake");
            otpContainer.addEventListener("animationend", () => otpContainer.classList.remove("shake"), { once: true });
        }
    };
    const focusOtpAt = (idx) => {
        const i = Math.max(0, Math.min(OTP_LENGTH - 1, idx));
        const el = otpCells[i];
        if (el) { el.focus(); el.select(); }
    };
    const clearOtpCells = () => {
        otpCells.forEach(c => { c.value = ""; c.classList.remove("filled", "error", "success", "otp-enter"); });
        syncHiddenOtp();
        setOtpStatus("idle", "");
        focusOtpAt(0);
    };
    const fillOtpFrom = (startIdx, text) => {
        const chars = text.split("").filter(c => OTP_ALLOW.test(c));
        if (chars.length === 0) return;
        let cursor = startIdx;
        for (const ch of chars) {
            if (cursor >= OTP_LENGTH) break;
            otpCells[cursor].value = ch;
            otpCells[cursor].classList.add("filled", "otp-enter");
            setTimeout(() => otpCells[cursor]?.classList.remove("otp-enter"), 220);
            cursor++;
        }
        syncHiddenOtp();
        if (cursor < OTP_LENGTH) focusOtpAt(cursor);
        else {
            otpCells[OTP_LENGTH - 1].focus();
            // auto-complete opcional como en React demo
            if (getOtpValue().length === OTP_LENGTH) {
                // no auto-submit, deja al usuario pulsar Confirmar
            }
        }
    };

    if (otpCells.length === OTP_LENGTH) {
        otpCells.forEach((cell, idx) => {
            cell.addEventListener("input", (e) => {
                const prev = cell.dataset.prev || "";
                let raw = e.target.value;
                // Maneja pegado o autocompletado con 2+ chars
                let incoming = raw;
                if (raw.length > 1) {
                    // si el valor previo estaba presente, quitarlo
                    if (prev && raw.startsWith(prev)) incoming = raw.slice(prev.length);
                    const filtered = incoming.split("").filter(c => OTP_ALLOW.test(c)).join("");
                    if (filtered.length === 0) {
                        cell.value = getOtpValue()[idx] || "";
                        return;
                    }
                    if (filtered.length === 1) {
                        cell.value = filtered;
                    } else {
                        // pegado múltiple
                        e.target.value = getOtpValue()[idx] || "";
                        fillOtpFrom(idx, filtered);
                        return;
                    }
                } else {
                    // single char
                    if (incoming && !OTP_ALLOW.test(incoming)) {
                        cell.value = "";
                        return;
                    }
                }
                cell.dataset.prev = cell.value;
                if (cell.value) {
                    cell.classList.add("filled", "otp-enter");
                    setTimeout(() => cell.classList.remove("otp-enter"), 220);
                } else {
                    cell.classList.remove("filled");
                }
                syncHiddenOtp();
                setOtpStatus("idle", "");
                if (cell.value && idx < OTP_LENGTH - 1) focusOtpAt(idx + 1);
            });

            cell.addEventListener("keydown", (e) => {
                if (e.key === "Backspace") {
                    e.preventDefault();
                    if (cell.value) {
                        cell.value = "";
                        cell.dataset.prev = "";
                        cell.classList.remove("filled");
                        syncHiddenOtp();
                    } else if (idx > 0) {
                        otpCells[idx - 1].value = "";
                        otpCells[idx - 1].dataset.prev = "";
                        otpCells[idx - 1].classList.remove("filled");
                        syncHiddenOtp();
                        focusOtpAt(idx - 1);
                    }
                    setOtpStatus("idle", "");
                    return;
                }
                if (e.key === "Delete") {
                    e.preventDefault();
                    cell.value = "";
                    cell.dataset.prev = "";
                    cell.classList.remove("filled");
                    syncHiddenOtp();
                    return;
                }
                if (e.key === "ArrowLeft") { e.preventDefault(); focusOtpAt(idx - 1); return; }
                if (e.key === "ArrowRight") { e.preventDefault(); focusOtpAt(idx + 1); return; }
                if (e.key === "Home") { e.preventDefault(); focusOtpAt(0); return; }
                if (e.key === "End") { e.preventDefault(); focusOtpAt(OTP_LENGTH - 1); return; }
                // Permitir solo números y controles
                if (e.key.length === 1 && !OTP_ALLOW.test(e.key) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            });

            cell.addEventListener("paste", (e) => {
                e.preventDefault();
                const text = (e.clipboardData.getData("text") || "").split("").filter(c => OTP_ALLOW.test(c)).join("");
                if (!text) return;
                // si pegan 6+ chars, llenar desde 0 como en React
                fillOtpFrom(text.length >= OTP_LENGTH ? 0 : idx, text);
            });

            cell.addEventListener("focus", (e) => {
                e.target.select();
                // si hay hueco antes, saltar al primer vacío (como React)
                const firstEmpty = otpCells.findIndex(c => !c.value);
                if (firstEmpty !== -1 && firstEmpty < idx) {
                    e.preventDefault();
                    focusOtpAt(firstEmpty);
                    return;
                }
                cell.classList.add("focused");
            });

            cell.addEventListener("blur", (e) => {
                cell.classList.remove("focused");
                // no quitar focusedIndex si el foco va a otra celda
                const to = e.relatedTarget;
                if (to && otpCells.includes(to)) return;
            });
        });
    }

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        if (!username || !password) {
            showAlert("Por favor, ingrese usuario y contraseña.", "error");
            triggerShake();
            return;
        }

        try {
            const res = await fetch("login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ username, password })
            });

            const rawText = await res.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (err) {
                showAlert("Error en respuesta PHP: " + rawText.substring(0, 100), "error");
                triggerShake();
                return;
            }

            if (data.status === "otp_required") {
                currentUserId = data.user_id;
                // Se guardan solo en memoria para el botón "Reenviar código"
                lastUsername = username;
                lastPassword = password;

                loginForm.classList.add("hidden");
                otpForm.classList.remove("hidden");

                formTitle.textContent = "Verificación 2FA";
                formSubtitle.textContent = `Revise su correo (${data.email_masked}) e introduzca el código.`;

                // El OTP solo viaja por correo: nunca se muestra en UI ni consola.
                showAlert(data.message, "success");
                // Inicializa celdas OTP vanilla (sin motion)
                clearOtpCells();
                setOtpStatus("idle", `Código enviado a ${data.email_masked}`);
                setTimeout(() => focusOtpAt(0), 100);
                startOtpCountdown();
            } else {
                showAlert(data.message || "Usuario o contraseña incorrectos.", "error");
                triggerShake();
                // Borrado letra por letra rápido en ambos campos
                const submitBtn = loginForm.querySelector('button[type="submit"]');
                usernameInput.disabled = true;
                passwordInput.disabled = true;
                if (togglePasswordBtn) togglePasswordBtn.disabled = true;
                if (submitBtn) submitBtn.disabled = true;

                setTimeout(async () => {
                    await Promise.all([
                        eraseLetterByLetter(usernameInput, 35),
                        eraseLetterByLetter(passwordInput, 35)
                    ]);
                    // Resetear ojo a estado oculto por seguridad
                    if (passwordInput.type === "text") {
                        passwordInput.type = "password";
                        if (togglePasswordBtn) {
                            const eyeOpen = togglePasswordBtn.querySelector(".eye-open");
                            const eyeClosed = togglePasswordBtn.querySelector(".eye-closed");
                            if (eyeOpen) eyeOpen.classList.remove("hidden");
                            if (eyeClosed) eyeClosed.classList.add("hidden");
                            togglePasswordBtn.setAttribute("aria-label", "Mostrar contraseña");
                        }
                    }
                    usernameInput.disabled = false;
                    passwordInput.disabled = false;
                    if (togglePasswordBtn) togglePasswordBtn.disabled = false;
                    if (submitBtn) submitBtn.disabled = false;
                    usernameInput.focus();
                }, 180);
            }
        } catch (err) {
            showAlert("Error al conectar con el servidor.", "error");
            triggerShake();
        }
    });

    otpForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const otp = getOtpValue().trim();
        syncHiddenOtp();
        if (otp.length !== 6) {
            showAlert("El código debe tener 6 dígitos.", "error");
            setOtpStatus("error", "El código debe tener 6 dígitos.");
            triggerShake();
            return;
        }

        try {
            const res = await fetch("verificacion.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ user_id: currentUserId, otp: otp })
            });

            const rawText = await res.text();
            let data;
            try {
                data = JSON.parse(rawText);
            } catch (err) {
                showAlert("Error en respuesta: " + rawText.substring(0, 100), "error");
                triggerShake();
                return;
            }

            if (data.status === "success") {
                if (otpTimerId) clearInterval(otpTimerId);
                setOtpStatus("success", data.message || "Código aceptado.");
                showAlert("Acceso permitido. Redirirgiendo...", "success");
                otpCells.forEach(c => { c.disabled = true; c.classList.add("success"); });
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 600);
            } else {
                showAlert(data.message || "Codigo incorrecto.", "error");
                setOtpStatus("error", data.message || "Código incorrecto.");
                triggerShake();
                // Borrado celda por celda rápido (adaptado de React clear)
                const submitBtnOtp = otpForm.querySelector('button[type="submit"]');
                otpCells.forEach(c => c.disabled = true);
                if (submitBtnOtp) submitBtnOtp.disabled = true;

                setTimeout(async () => {
                    for (let i = OTP_LENGTH - 1; i >= 0; i--) {
                        otpCells[i].value = "";
                        otpCells[i].dataset.prev = "";
                        otpCells[i].classList.remove("filled", "success", "error", "otp-enter");
                        syncHiddenOtp();
                        await new Promise(r => setTimeout(r, 35));
                    }
                    otpCells.forEach(c => c.disabled = false);
                    if (submitBtnOtp) submitBtnOtp.disabled = false;
                    setOtpStatus("idle", "");
                    focusOtpAt(0);
                }, 180);
            }
        } catch (err) {
            showAlert("Error al validar el código: " + err.message, "error");
            triggerShake();
        }
    });

    function eraseLetterByLetter(input, interval = 35) {
        return new Promise((resolve) => {
            if (!input.value) {
                resolve();
                return;
            }
            const timer = setInterval(() => {
                if (input.value.length > 0) {
                    input.value = input.value.slice(0, -1);
                    input.dispatchEvent(new Event("input"));
                } else {
                    clearInterval(timer);
                    resolve();
                }
            }, interval);
        });
    }

    function triggerShake() {
        if (!card) return;
        card.classList.remove("shake");
        void card.offsetWidth;
        card.classList.add("shake");
        card.addEventListener("animationend", () => {
            card.classList.remove("shake");
        }, { once: true });
    }

    // Efecto 3D muy sutil al mover el cursor por las esquinas
    const mainContainer = document.querySelector(".main-container");
    if (mainContainer && card && !window.matchMedia("(prefers-reduced-motion: reduce)").matches && !window.matchMedia("(pointer: coarse)").matches) {
        const glare = document.createElement("div");
        glare.className = "card-glare";
        card.appendChild(glare);

        let targetX = 0, targetY = 0;
        let currentX = 0, currentY = 0;
        const maxTilt = 2.2; // extra sutil, antes 4.5
        const perspective = 1300; // más distancia = menos pronunciado

        function lerp(a, b, t) { return a + (b - a) * t; }

        function updateTilt() {
            if (card.classList.contains("shake")) {
                targetX = 0;
                targetY = 0;
            }
            currentX = lerp(currentX, targetX, 0.045);
            currentY = lerp(currentY, targetY, 0.045);

            const nearZero = Math.abs(currentX) < 0.03 && Math.abs(currentY) < 0.03;
            if (nearZero && targetX === 0 && targetY === 0) {
                card.style.transform = "";
                card.classList.remove("is-tilting");
            } else {
                card.style.transform = `perspective(${perspective}px) rotateX(${currentY}deg) rotateY(${currentX}deg) translateZ(0)`;
                card.classList.add("is-tilting");
            }
            requestAnimationFrame(updateTilt);
        }
        updateTilt();

        let isHovering = false;
        mainContainer.addEventListener("mousemove", (e) => {
            if (card.classList.contains("shake")) return;
            const rect = card.getBoundingClientRect();
            const extended = 90;
            if (e.clientX < rect.left - extended || e.clientX > rect.right + extended || e.clientY < rect.top - extended || e.clientY > rect.bottom + extended) {
                if (isHovering) {
                    isHovering = false;
                    targetX = 0;
                    targetY = 0;
                    glare.style.opacity = "0";
                }
                return;
            }
            isHovering = true;
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const deltaX = e.clientX - centerX;
            const deltaY = e.clientY - centerY;
            const normX = deltaX / (rect.width / 2);
            const normY = deltaY / (rect.height / 2);
            targetX = Math.max(-maxTilt, Math.min(maxTilt, normX * maxTilt));
            targetY = Math.max(-maxTilt, Math.min(maxTilt, -normY * maxTilt));

            const xPct = ((e.clientX - rect.left) / rect.width) * 100;
            const yPct = ((e.clientY - rect.top) / rect.height) * 100;
            glare.style.setProperty("--mx", xPct + "%");
            glare.style.setProperty("--my", yPct + "%");
            glare.style.opacity = "0.55";

            const shadowX = -targetX * 0.7;
            const shadowY = -targetY * 0.7 + 20;
            card.style.boxShadow = `${shadowX}px ${shadowY}px 60px -15px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.03), inset 0 1px 0 rgba(255,255,255,0.06)`;
        });

        mainContainer.addEventListener("mouseleave", () => {
            targetX = 0;
            targetY = 0;
            isHovering = false;
            glare.style.opacity = "0";
            setTimeout(() => {
                if (!isHovering) card.style.boxShadow = "";
            }, 200);
        });

        card.addEventListener("animationend", (e) => {
            if (e.animationName === "shake") {
                targetX = 0;
                targetY = 0;
            }
        });
    }

    // Partículas estilo nodos + fondo animado sutil
    const bgCanvas = document.getElementById("bg-particles");
    if (bgCanvas && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        const ctx = bgCanvas.getContext("2d", { alpha: true });
        const container = document.querySelector(".main-container");
        let particles = [];
        let mouse = { x: -9999, y: -9999 };
        let rafParticles = null;
        const COUNT_DESKTOP = 58;
        const COUNT_MOBILE = 28;
        const LINK_DIST = 150;
        const MOUSE_DIST = 180;

        function isMobile() { return window.innerWidth < 768; }

        function resizeCanvas() {
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            bgCanvas.width = rect.width * dpr;
            bgCanvas.height = rect.height * dpr;
            bgCanvas.style.width = rect.width + "px";
            bgCanvas.style.height = rect.height + "px";
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(dpr, dpr);
            initParticles();
        }

        function initParticles() {
            const rect = container.getBoundingClientRect();
            const w = rect.width, h = rect.height;
            const count = isMobile() ? COUNT_MOBILE : COUNT_DESKTOP;
            particles = Array.from({ length: count }, () => ({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.32,
                vy: (Math.random() - 0.5) * 0.32,
                r: Math.random() * 1.1 + 0.9,
                baseAlpha: Math.random() * 0.22 + 0.38,
                pulse: Math.random() * Math.PI * 2
            }));
        }

        function updateParticles() {
            if (!container) return;
            const rect = container.getBoundingClientRect();
            const w = rect.width, h = rect.height;
            ctx.clearRect(0, 0, w, h);

            // actualizar y dibujar conexiones
            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                p.pulse += 0.012;

                // rebote suave en bordes
                if (p.x < 0 || p.x > w) p.vx *= -1;
                if (p.y < 0 || p.y > h) p.vy *= -1;
                p.x = Math.max(0, Math.min(w, p.x));
                p.y = Math.max(0, Math.min(h, p.y));

                // atracción muy sutil al mouse (efecto nodo central)
                const mdx = mouse.x - p.x;
                const mdy = mouse.y - p.y;
                const mdist = Math.hypot(mdx, mdy);
                if (mdist < MOUSE_DIST) {
                    const force = (1 - mdist / MOUSE_DIST) * 0.015;
                    p.vx += (mdx / mdist) * force;
                    p.vy += (mdy / mdist) * force;
                    p.vx = Math.max(-0.6, Math.min(0.6, p.vx));
                    p.vy = Math.max(-0.6, Math.min(0.6, p.vy));
                }
            }

            // líneas entre nodos
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const a = particles[i], b = particles[j];
                    const dx = a.x - b.x, dy = a.y - b.y;
                    const dist = Math.hypot(dx, dy);
                    if (dist < LINK_DIST) {
                        const alpha = (1 - dist / LINK_DIST) * 0.095;
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = `rgba(148, 163, 184, ${alpha})`;
                        ctx.lineWidth = 0.65;
                        ctx.stroke();
                    }
                }
                // línea al mouse
                const mdx = mouse.x - particles[i].x;
                const mdy = mouse.y - particles[i].y;
                const mdist = Math.hypot(mdx, mdy);
                if (mdist < MOUSE_DIST) {
                    const alpha = (1 - mdist / MOUSE_DIST) * 0.13;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.strokeStyle = `rgba(10, 132, 255, ${alpha})`;
                    ctx.lineWidth = 0.7;
                    ctx.stroke();
                }
            }

            // nodos
            for (const p of particles) {
                const pulseAlpha = p.baseAlpha + Math.sin(p.pulse) * 0.12;
                // halo exterior muy sutil
                const grad = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 3.2);
                grad.addColorStop(0, `rgba(59, 130, 246, ${pulseAlpha * 0.18})`);
                grad.addColorStop(1, "rgba(59, 130, 246, 0)");
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r * 3.2, 0, Math.PI * 2);
                ctx.fill();

                // núcleo
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(203, 213, 225, ${pulseAlpha})`;
                ctx.fill();
                // punto central blanco
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r * 0.45, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${pulseAlpha * 0.9})`;
                ctx.fill();
            }

            // punto mouse sutil
            if (mouse.x > 0 && mouse.y > 0) {
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, 1.8, 0, Math.PI * 2);
                ctx.fillStyle = "rgba(10, 132, 255, 0.55)";
                ctx.fill();
            }

            rafParticles = requestAnimationFrame(updateParticles);
        }

        if (container) {
            container.addEventListener("mousemove", (e) => {
                const rect = container.getBoundingClientRect();
                mouse.x = e.clientX - rect.left;
                mouse.y = e.clientY - rect.top;
            });
            container.addEventListener("mouseleave", () => {
                mouse.x = -9999; mouse.y = -9999;
            });
        }

        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();
        updateParticles();

        // pausar si pestaña oculta
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                if (rafParticles) cancelAnimationFrame(rafParticles);
            } else {
                updateParticles();
            }
        });
    }

    function showAlert(msg, type) {
        alertBox.textContent = msg;
        alertBox.className = `alert ${type}`;
        alertBox.classList.remove("hidden");
    }

    // Cuenta regresiva de 5:00 del OTP + botón Reenviar código
    function startOtpCountdown() {
        const timerEl = document.getElementById("otp-timer");
        const resendBtn = document.getElementById("resend-otp");
        if (otpTimerId) clearInterval(otpTimerId);
        let remaining = OTP_TTL_SECS;
        const render = () => {
            if (!timerEl) return;
            const m = Math.floor(remaining / 60);
            const s = String(remaining % 60).padStart(2, "0");
            timerEl.textContent = remaining > 0
                ? `El código vence en ${m}:${s}`
                : "El código venció. Solicite uno nuevo.";
        };
        render();
        if (resendBtn) resendBtn.disabled = false;
        otpTimerId = setInterval(() => {
            remaining--;
            render();
            if (remaining <= 0) clearInterval(otpTimerId);
        }, 1000);
    }

    const resendBtn = document.getElementById("resend-otp");
    if (resendBtn) {
        resendBtn.addEventListener("click", async () => {
            if (!lastUsername || !lastPassword) return;
            resendBtn.disabled = true;
            try {
                const res = await fetch("login.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ username: lastUsername, password: lastPassword })
                });
                const data = await res.json();
                if (data.status === "otp_required") {
                    currentUserId = data.user_id;
                    clearOtpCells();
                    setOtpStatus("idle", `Nuevo código enviado a ${data.email_masked}`);
                    showAlert(data.message, "success");
                    startOtpCountdown();
                } else {
                    showAlert(data.message || "No se pudo reenviar el código.", "error");
                    resendBtn.disabled = false;
                }
            } catch (err) {
                showAlert("Error al reenviar el código.", "error");
                resendBtn.disabled = false;
            }
        });
    }
});
