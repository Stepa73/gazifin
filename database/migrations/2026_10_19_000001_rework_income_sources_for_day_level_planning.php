<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dovolená byla dvanáct čísel — počet dnů v měsíci. Tím se ale vázala na měsíc,
     * ne na konkrétní den: kalendář z ní nešlo postavit a v dalším roce se tatáž
     * čísla použila znovu, takže si člověk „vybral dovolenou“ i v roce, který
     * vůbec neplánoval. Nově je to seznam konkrétních dnů.
     */
    public function up(): void
    {
        Schema::table('income_sources', function (Blueprint $table) {
            $table->json('vacation_days')->nullable()->after('vacation');
            // Které dny v týdnu se pro zdroj pracuje (1 = pondělí … 7 = neděle).
            $table->json('work_days')->nullable()->after('hours_per_day');
        });

        foreach (DB::table('income_sources')->get() as $source) {
            DB::table('income_sources')
                ->where('id', $source->id)
                ->update([
                    'vacation_days' => json_encode($this->spread($source)),
                    'work_days' => json_encode([1, 2, 3, 4, 5]),
                ]);
        }

        Schema::table('income_sources', function (Blueprint $table) {
            $table->dropColumn('vacation');
        });
    }

    public function down(): void
    {
        Schema::table('income_sources', function (Blueprint $table) {
            $table->json('vacation')->nullable()->after('invoice_amount');
        });

        foreach (DB::table('income_sources')->get() as $source) {
            $counts = array_fill(0, 12, 0);

            foreach (json_decode($source->vacation_days ?? '[]', true) ?: [] as $day) {
                $month = (int) Carbon::parse($day)->format('n') - 1;
                $counts[$month]++;
            }

            DB::table('income_sources')->where('id', $source->id)->update(['vacation' => json_encode($counts)]);
        }

        Schema::table('income_sources', function (Blueprint $table) {
            $table->dropColumn(['vacation_days', 'work_days']);
        });
    }

    /**
     * Ze starého počtu dnů se konkrétní data dozvědět nedají. Nejblíž původnímu
     * záměru je rozprostřít je na první pracovní dny měsíce — součet za měsíc
     * tím zůstane stejný, takže se výsledek nerozejde.
     *
     * @return array<int, string>
     */
    private function spread(object $source): array
    {
        $counts = json_decode($source->vacation ?? '[]', true);

        if (! is_array($counts)) {
            return [];
        }

        $year = (int) Carbon::parse($source->starts_on ?? now())->format('Y');
        $days = [];

        foreach ($counts as $month => $count) {
            $count = (int) $count;

            if ($count < 1) {
                continue;
            }

            $cursor = Carbon::create($year, (int) $month + 1, 1);
            $taken = 0;

            while ($taken < $count && (int) $cursor->format('n') === (int) $month + 1) {
                if (! $cursor->isWeekend()) {
                    $days[] = $cursor->format('Y-m-d');
                    $taken++;
                }

                $cursor->addDay();
            }
        }

        return $days;
    }
};
