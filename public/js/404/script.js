$(function() {
    var $copyContainer = $(".copy-container"),
        $replayIcon = $('#cb-replay'),
        $copyParagraph = $copyContainer.find('p');

    var copyWidth = $copyParagraph.width();

    var text = $copyParagraph.text();
    var chars = text.split('');
    var html = '';
    chars.forEach(function(char) {
        if(char === ' ') {
            html += '<span class="char" style="white-space:pre;">&nbsp;</span>';
        } else {
            html += '<span class="char">' + char + '</span>';
        }
    });
    $copyParagraph.html(html);

    var splitTextTimeline = gsap.timeline({ paused: true });
    splitTextTimeline.fromTo('.char',
        { autoAlpha: 0 },
        {
            autoAlpha: 1,
            stagger: 0.05,
            duration: 0.1,
            ease: "back.inOut(1.7)",
            onComplete: animateHandle
        }
    );

    var handleTL = gsap.timeline({ repeat: -1, yoyo: true, paused: true });

    function blinkHandle() {
        handleTL.fromTo('.handle',
            { autoAlpha: 0 },
            { autoAlpha: 1, duration: 0.4 }
        );
    }

    function animateHandle() {
        blinkHandle();
        handleTL.play();

        gsap.to('.handle', {
            x: copyWidth,
            duration: 0.7,
            ease: "steps(12)",
            delay: 0.05,
            onComplete: function() {
            }
        });
    }

    gsap.delayedCall(0.2, function() {
        splitTextTimeline.restart();
    });

    $replayIcon.on('click', function() {
        splitTextTimeline.restart();
        handleTL.restart();
        gsap.to('.handle', { x: 0, duration: 0 });
    });
});
