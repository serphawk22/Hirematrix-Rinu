(function () {
    'use strict';

    var AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) {
        return;
    }

    var context = null;
    var activePlayer = null;
    var masterGain = null;
    var timerId = null;
    var step = 0;
    var nextNoteAt = 0;
    var mediaSources = new WeakMap();
    var bgmLevel = 0.48;

    // A restrained, non-vocal pulse designed to sit behind a product walkthrough.
    var progression = [
        [130.81, 164.81, 196.00],
        [110.00, 130.81, 164.81],
        [87.31, 130.81, 174.61],
        [98.00, 146.83, 196.00]
    ];

    function getContext() {
        if (!context) {
            context = new AudioContextClass();
        }
        return context;
    }

    function ensureContext() {
        getContext();
        if (context.state === 'suspended') {
            context.resume();
        }
        return context;
    }

    function suppressOriginalAudio(player) {
        getContext();
        if (mediaSources.has(player)) {
            return;
        }

        var source = context.createMediaElementSource(player);
        var silentOutput = context.createGain();
        silentOutput.gain.value = 0;
        source.connect(silentOutput);
        silentOutput.connect(context.destination);
        mediaSources.set(player, { source: source, output: silentOutput });
    }

    function selectedVolume(player) {
        return player.muted ? 0.0001 : Math.max(0.0001, player.volume * bgmLevel);
    }

    function scheduleTone(frequency, startsAt, duration, level) {
        var oscillator = context.createOscillator();
        var gain = context.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(frequency, startsAt);
        gain.gain.setValueAtTime(0.0001, startsAt);
        gain.gain.exponentialRampToValueAtTime(level, startsAt + 0.18);
        gain.gain.exponentialRampToValueAtTime(0.0001, startsAt + duration);

        oscillator.connect(gain);
        gain.connect(masterGain);
        oscillator.start(startsAt);
        oscillator.stop(startsAt + duration + 0.05);
    }

    function scheduleAhead() {
        while (nextNoteAt < context.currentTime + 0.5) {
            var chord = progression[Math.floor(step / 4) % progression.length];
            var note = chord[step % chord.length];

            scheduleTone(note, nextNoteAt, 1.35, 0.18);
            if (step % 4 === 0) {
                scheduleTone(chord[0] / 2, nextNoteAt, 2.8, 0.10);
            }

            step += 1;
            nextNoteAt += 0.72;
        }
    }

    function stopBgm(player) {
        if (player && activePlayer && player !== activePlayer) {
            return;
        }
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
        if (masterGain && context) {
            masterGain.gain.cancelScheduledValues(context.currentTime);
            masterGain.gain.setTargetAtTime(0.0001, context.currentTime, 0.08);
        }
        activePlayer = null;
    }

    function startBgm(player) {
        stopBgm();
        ensureContext();

        activePlayer = player;
        step = 0;
        nextNoteAt = context.currentTime + 0.04;
        masterGain = context.createGain();
        masterGain.gain.setValueAtTime(0.0001, context.currentTime);
        masterGain.gain.exponentialRampToValueAtTime(selectedVolume(player), context.currentTime + 0.25);
        masterGain.connect(context.destination);

        scheduleAhead();
        timerId = window.setInterval(scheduleAhead, 180);
    }

    function preparePlayer(player) {
        // Route the embedded song to silence while retaining the native volume UI.
        suppressOriginalAudio(player);
        player.defaultMuted = false;
        player.muted = false;
        player.removeAttribute('muted');

        player.addEventListener('play', function () {
            startBgm(player);
        });
        player.addEventListener('pause', function () { stopBgm(player); });
        player.addEventListener('ended', function () { stopBgm(player); });
        player.addEventListener('emptied', function () { stopBgm(player); });
        player.addEventListener('volumechange', function () {
            if (masterGain && context && player === activePlayer) {
                masterGain.gain.cancelScheduledValues(context.currentTime);
                masterGain.gain.setTargetAtTime(selectedVolume(player), context.currentTime, 0.04);
            }
        });
    }

    function init() {
        document.querySelectorAll('video[data-ai-tour-bgm]').forEach(preparePlayer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
