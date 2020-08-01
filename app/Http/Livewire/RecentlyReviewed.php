<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RecentlyReviewed extends Component
{
    public $recentlyReviewed = [];

    public function loadRecentlyReviewed()
    {
        $before = Carbon::now()->subMonths(2)->timestamp;
        $current = Carbon::now()->timestamp;

        $recentlyReviewedUnFormatted = Cache::remember('recently-reviewed', 7, function () use ($before, $current) {
            // sleep(3);
            return Http::withHeaders(config('services.igdb'))
                ->withOptions([
                    'body' => "
                        fields name, cover.url, first_release_date, popularity, platforms.abbreviation, rating, rating_count, summary, slug;
                        where platforms = (48,49,130,6)
                        & (first_release_date >= {$before}
                        & first_release_date < {$current}
                        & rating_count > 5);
                        sort popularity desc;
                        limit 3;
                    "
                ])->get('https://api-v3.igdb.com/games')
                ->json();
        });

        $this->recentlyReviewed = $this->formatForView($recentlyReviewedUnFormatted);
    }

    public function render()
    {
        return view('livewire.recently-reviewed');
    }

    public function formatForView($games)
    {
        return collect($games)->map(function ($game){
            return collect($game)->merge([
                'coverImageUrl' => Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']),
                'rating' => isset($game['rating']) ? round($game['rating']).'%' : null,
                'platforms' => collect($game['platforms'])->pluck('abbreviation')->implode(', '),
            ]);
        })->toArray();
    }
}
