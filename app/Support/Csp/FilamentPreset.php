<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Scheme;

/**
 * CSP policy tuned for Filament (Livewire + Alpine.js).
 *
 * Filament cannot run under a nonce-based policy: Alpine evaluates its
 * expressions with the Function constructor (requires 'unsafe-eval'), and
 * Livewire/Filament emit inline <script>/<style> tags plus inline style
 * attributes that cannot be nonced. Because a nonce makes browsers ignore
 * 'unsafe-inline', nonces must stay disabled (see config/csp.php).
 */
class FilamentPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::MANIFEST, Keyword::SELF)

            // Livewire XHR / polling. Add wss:/ws: here if you use Reverb/Echo.
            ->add(Directive::CONNECT, Keyword::SELF)

            // Alpine needs 'unsafe-eval'; Livewire/Filament emit inline scripts.
            ->add(Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_INLINE, Keyword::UNSAFE_EVAL])

            // Filament injects inline styles and style="" attributes.
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])

            // data: for inline SVG icons, https: for remote avatars (ui-avatars,
            // gravatar, etc.), blob: for client-side file/image previews.
            ->add(Directive::IMG, [Keyword::SELF, Scheme::DATA, Scheme::HTTPS, Scheme::BLOB])

            ->add(Directive::FONT, [Keyword::SELF, Scheme::DATA])
            ->add(Directive::MEDIA, [Keyword::SELF, Scheme::DATA, Scheme::BLOB])

            // Some bundled libraries spin up web workers from blob: URLs.
            ->add(Directive::WORKER, [Keyword::SELF, Scheme::BLOB]);
    }
}
