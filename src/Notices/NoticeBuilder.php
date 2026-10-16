<?php

namespace Vipertecpro\DataRightsDesk\Notices;

use Illuminate\Support\Facades\Route;
use Vipertecpro\DataRightsDesk\Purposes\PurposeRegistry;
use Vipertecpro\DataRightsDesk\Support\BuiltNotice;
use Vipertecpro\DataRightsDesk\Support\Contact;
use Vipertecpro\DataRightsDesk\Support\Languages;
use Vipertecpro\DataRightsDesk\Support\Limits;
use Vipertecpro\DataRightsDesk\Support\Purpose;

/**
 * Builds the notice of s.5(1) and Rule 3 as standalone Markdown:
 *
 * Rule 3(a): understandable independently of any other information;
 * Rule 3(b): an itemised description of the personal data and the specified
 * purpose, with the goods or services provided or uses enabled;
 * Rule 3(c): the link to withdraw consent ("with the ease of doing so being
 * comparable to that with which such consent was given"), to exercise rights,
 * and to complain to the Board.
 */
class NoticeBuilder
{
    public function __construct(private PurposeRegistry $purposes) {}

    public function build(string $locale): BuiltNotice
    {
        $t = fn (string $key, array $replace = []): string => (string) __("data-rights::notice.{$key}", $replace, $locale);
        $business = Contact::businessName();
        $purposes = $this->purposes->all();
        $missing = [];

        $lines = [];
        $lines[] = '# '.$t('heading', ['business' => $business]);
        $lines[] = '';
        $lines[] = $t('intro', ['business' => $business]);
        $lines[] = '';
        $lines[] = '## '.$t('purposes_heading');

        foreach ($purposes as $purpose) {
            array_push($missing, ...$purpose->missingFor($locale));
            array_push($lines, ...$this->purposeSection($purpose, $locale, $t));
        }

        $lines[] = '';
        $lines[] = '## '.$t('withdraw_heading');
        $lines[] = '';
        $lines[] = $t('withdraw_body', ['url' => $this->link('data-rights.preferences', $locale)]);
        $lines[] = '';
        $lines[] = '## '.$t('rights_heading');
        $lines[] = '';
        $lines[] = $t('rights_body', ['url' => $this->link('data-rights.index', $locale), 'days' => Limits::responseDays()]);
        $lines[] = '';
        $lines[] = '## '.$t('board_heading');
        $lines[] = '';
        $board = config('data-rights.board.url');
        $lines[] = filled($board) && is_string($board)
            ? $t('board_with_url', ['url' => '<'.$board.'>'])
            : $t('board_without_url');
        $lines[] = '';
        $lines[] = '## '.$t('contact_heading');
        $lines[] = '';
        $lines[] = $t('contact_intro');
        $lines[] = '';
        $lines[] = Contact::markdown($locale);
        $lines[] = '';
        $lines[] = '## '.$t('languages_heading');
        $lines[] = '';
        $lines[] = $t('languages_body', [
            'languages' => implode(', ', array_map(fn (string $code): string => Languages::nativeName($code), Languages::offered())),
        ]);

        $body = implode("\n", $lines)."\n";

        return new BuiltNotice(
            locale: $locale,
            body: $body,
            hash: hash('sha256', $body),
            purposes: array_map(fn (Purpose $purpose): array => $purpose->snapshot(), $purposes),
            missing: array_values(array_unique($missing)),
        );
    }

    /**
     * @param  callable(string, array<string, mixed>=): string  $t
     * @return list<string>
     */
    private function purposeSection(Purpose $purpose, string $locale, callable $t): array
    {
        $lines = ['', '### '.$purpose->title($locale), ''];

        if ($purpose->description($locale) !== '') {
            $lines[] = $purpose->description($locale);
            $lines[] = '';
        }

        $lines[] = '**'.$t('label_uses').':** '.$purpose->goodsOrServices($locale);
        $lines[] = '';
        $lines[] = '**'.$t('label_data').':**';
        $lines[] = '';

        foreach ($purpose->data($locale) as $item) {
            $lines[] = '- '.$item;
        }

        $lines[] = '';
        $basis = match (true) {
            $purpose->needsConsent() => $t('basis_consent'),
            $purpose->canBeObjectedTo() => $t('basis_legitimate_use_a'),
            default => $t('basis_legitimate_use', ['clause' => (string) $purpose->legitimateUse]),
        };
        $lines[] = '**'.$t('label_basis').':** '.$basis;

        $effect = $purpose->withdrawalEffect($locale);

        if ($effect !== null) {
            $lines[] = '';
            $lines[] = '**'.$t('label_withdrawal_effect').':** '.$effect;
        }

        if ($purpose->tracking && ! $purpose->hasChildExemption() && (bool) config('data-rights.children.enabled', true)) {
            $lines[] = '';
            $lines[] = $t('tracking');
        }

        return $lines;
    }

    /**
     * Built from app.url rather than the current request, so the same config
     * gives the same notice (and hash) from the console and from the web.
     */
    private function link(string $route, string $locale): string
    {
        if (Route::has($route)) {
            $parameters = $locale === Languages::defaultLocale() ? [] : ['lang' => $locale];
            $root = rtrim((string) config('app.url', ''), '/');

            return '<'.$root.route($route, $parameters, false).'>';
        }

        $website = config('data-rights.business.website');

        return is_string($website) && $website !== '' ? '<'.$website.'>' : '';
    }
}
