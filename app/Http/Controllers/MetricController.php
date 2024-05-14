<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\UserStampCard;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetricController extends Controller
{
    /**
     * Visitas de Clientes (por negocio) | Cantidad x periodo | Porcentaje de crecimiento en comparación con el periodo anterior
     * Recompensas Canjeadas (por negocio) | Cantidad x periodo | Porcentaje de crecimiento en comparación con el periodo anterior
     * Tarjetas completadas (por negocio) (Visitas requeridas cumplidas) | Cantidad x periodo | Porcentaje de crecimiento en comparación con el periodo anterior
     * Usuarios Activos (por negocio) | Cantidad x periodo | Porcentaje de crecimiento en comparación con el periodo anterior
     *
     * Clientes más frecuentes (por negocio) | Top 3 clientes con más visitas
     *
     * Colección de visitas por mes (por negocio) | Cantidad de visitas por mes
     */

     public function getGlobalMetrics(Request $request){

        //Verify if the business_id belongs to the user business's list
        $businessId = $request->business_id;
        $userId = auth()->user()->id;
        $businesses = auth()->user()->businesses;
        $businessesId = $businesses->pluck('id')->toArray();
        if(!in_array($businessId, $businessesId)){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => ['Business not reachable']
                ],
                404
            );
        }

        $timePeriod = $request->query('timePeriod');




        $timePeriod = $request->time_period;
        $metrics = [
            'visits' => $this->getVisitsPerBusiness($businessId, $timePeriod),
            'redeemedRewards' => $this->getRedeemedRewardsPerBusiness($businessId, $timePeriod),
            'completedStampCards' => $this->getCompletedStampCards($businessId, $timePeriod),
            'activeUsers' => $this->getActiveUsers($businessId, $timePeriod),
            'topClients' => $this->getTopClients($businessId, $timePeriod),
            'visitsByMonth' => $this->getVisitsByMonth($businessId),
            'mostVisitedMonth' => $this->getMostVisitedMonth($businessId)

        ];

        return response()->json(
            [
                'status' => 'success',
                'data' => $metrics
            ],
            200
        );

     }

     public function getVisitsPerBusiness($businessId, $timePeriod){

        if ($timePeriod == 'day'){
            $currentTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereDate('created_at', Carbon::today())
            ->count();

            $previousTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereDate('created_at', Carbon::yesterday())
            ->count();

            $visits = [
                'current' => $currentTimePeriodVisits,
                'previous' => $previousTimePeriodVisits,
                'growth' => $previousTimePeriodVisits != 0 ? (($currentTimePeriodVisits - $previousTimePeriodVisits) / $previousTimePeriodVisits) * 100 : 100
            ];

        } else if ($timePeriod == 'week'){
            $currentTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();

            $previousTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();

            $visits = [
                'current' => $currentTimePeriodVisits,
                'previous' => $previousTimePeriodVisits,
                'growth' => $previousTimePeriodVisits != 0 ? (($currentTimePeriodVisits - $previousTimePeriodVisits) / $previousTimePeriodVisits) * 100 : 100
            ];

        } else if ($timePeriod == 'month'){
            $currentTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

            $previousTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();

            $visits = [
                'current' => $currentTimePeriodVisits,
                'previous' => $previousTimePeriodVisits,
                'growth' => $previousTimePeriodVisits != 0 ? (($currentTimePeriodVisits - $previousTimePeriodVisits) / $previousTimePeriodVisits) * 100 : 100
            ];

        } else if ($timePeriod == 'year'){
            $currentTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

            $previousTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->whereYear('created_at', Carbon::now()->subYear()->year)
            ->count();
        } else { //Month as default
            $currentTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->whereMonth('created_at', Carbon::now()->month)
                ->count();

                $previousTimePeriodVisits = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->count();

                $visits = [
                    'current' => $currentTimePeriodVisits,
                    'previous' => $previousTimePeriodVisits,
                    'growth' => $previousTimePeriodVisits != 0 ? (($currentTimePeriodVisits - $previousTimePeriodVisits) / $previousTimePeriodVisits) * 100 : 100
                ];

        }
        return $visits;
     }

    public function getRedeemedRewardsPerBusiness($businessId, $timePeriod){

        if ($timePeriod == 'day'){
            $currentTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereDate('updated_at', Carbon::today())
            ->count();

            $previousTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereDate('updated_at', Carbon::yesterday())
            ->count();

            $growth = $previousTimePeriodRedeemedRewards != 0 ? (($currentTimePeriodRedeemedRewards - $previousTimePeriodRedeemedRewards) / $previousTimePeriodRedeemedRewards) * 100 : 100;

        } else if ($timePeriod == 'week'){
            $currentTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();

            $previousTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereBetween('updated_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();

            $growth = $previousTimePeriodRedeemedRewards != 0 ? (($currentTimePeriodRedeemedRewards - $previousTimePeriodRedeemedRewards) / $previousTimePeriodRedeemedRewards) * 100 : 100;
        } else if ($timePeriod == 'month'){
            $currentTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_reward_redeemed', 1)
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count();

                $previousTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_reward_redeemed', 1)
                ->whereMonth('updated_at', Carbon::now()->subMonth()->month)
                ->count();

                $growth = $previousTimePeriodRedeemedRewards != 0 ? (($currentTimePeriodRedeemedRewards - $previousTimePeriodRedeemedRewards) / $previousTimePeriodRedeemedRewards) * 100 : 100;
            $growth = $previousTimePeriodRedeemedRewards != 0 ? (($currentTimePeriodRedeemedRewards - $previousTimePeriodRedeemedRewards) / $previousTimePeriodRedeemedRewards) * 100 : 100;
        } else if ($timePeriod == 'year'){
            $currentTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereYear('updated_at', Carbon::now()->year)
            ->count();

            $previousTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_reward_redeemed', 1)
            ->whereYear('updated_at', Carbon::now()->subYear()->year)
            ->count();
        } else { //Month as default
            $currentTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_reward_redeemed', 1)
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count();

                $previousTimePeriodRedeemedRewards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_reward_redeemed', 1)
                ->whereMonth('updated_at', Carbon::now()->subMonth()->month)
                ->count();

                $growth = $previousTimePeriodRedeemedRewards != 0 ? (($currentTimePeriodRedeemedRewards - $previousTimePeriodRedeemedRewards) / $previousTimePeriodRedeemedRewards) * 100 : 100;

        }

        $redeemedRewards = [
            'current' => $currentTimePeriodRedeemedRewards,
            'previous' => $previousTimePeriodRedeemedRewards,
            'growth' => $growth
        ];

        return $redeemedRewards;
    }

    public function getCompletedStampCards($businessId, $timePeriod){

        if ($timePeriod == 'day'){
            $currentTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereDate('updated_at', Carbon::today())
            ->count();

            $previousTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereDate('updated_at', Carbon::yesterday())
            ->count();

            $growth = $previousTimePeriodCompletedStampCards != 0 ? (($currentTimePeriodCompletedStampCards - $previousTimePeriodCompletedStampCards) / $previousTimePeriodCompletedStampCards) * 100 : 100;

        } else if ($timePeriod == 'week'){
            $currentTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->count();

            $previousTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereBetween('updated_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])
            ->count();

            $growth = $previousTimePeriodCompletedStampCards != 0 ? (($currentTimePeriodCompletedStampCards - $previousTimePeriodCompletedStampCards) / $previousTimePeriodCompletedStampCards) * 100 : 100;
        } else if ($timePeriod == 'month'){
            $currentTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_completed', 1)
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count();

                $previousTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_completed', 1)
                ->whereMonth('updated_at', Carbon::now()->subMonth()->month)
                ->count();


            $growth = $previousTimePeriodCompletedStampCards != 0 ? (($currentTimePeriodCompletedStampCards - $previousTimePeriodCompletedStampCards) / $previousTimePeriodCompletedStampCards) * 100 : 100;
        } else if ($timePeriod == 'year'){
            $currentTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereYear('updated_at', Carbon::now()->year)
            ->count();

            $previousTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_completed', 1)
            ->whereYear('updated_at', Carbon::now()->subYear()->year)
            ->count();
        } else { //Month as default
            $currentTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_completed', 1)
                ->whereMonth('updated_at', Carbon::now()->month)
                ->count();

                $previousTimePeriodCompletedStampCards = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
                ->where('is_completed', 1)
                ->whereMonth('updated_at', Carbon::now()->subMonth()->month)
                ->count();

                $growth = $previousTimePeriodCompletedStampCards != 0 ? (($currentTimePeriodCompletedStampCards - $previousTimePeriodCompletedStampCards) / $previousTimePeriodCompletedStampCards) * 100 : 100;

        }

        $completedStampCards = [
            'current' => $currentTimePeriodCompletedStampCards,
            'previous' => $previousTimePeriodCompletedStampCards,
            'growth' => $growth
        ];

        return $completedStampCards;
    }

    public function getActiveUsers($businessId, $timePeriod){

        //Get the users that have a UserStampCard in the business with is_active = 1, is_completed = 0. This metric is accumulated

        $activeUsers = UserStampCard::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->where('is_active', 1)
            ->where('is_completed', 0)
            ->count();

        return $activeUsers;

    }

    public function getTopClients($businessId, $timePeriod){

       //Get the user with the most visits in the business in the time period

        if ($timePeriod == 'week'){
            $topClients = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
            ->select('user_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('user_id')
            ->orderByRaw('visits DESC')
            ->limit(3)
            ->with('user:id,first_name,last_name,repitt_code')
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->get();
        } else if ($timePeriod == 'month'){
            $topClients = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
            ->select('user_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('user_id')
            ->orderByRaw('visits DESC')
            ->limit(3)
            ->with('user:id,first_name,last_name,repitt_code')
            ->whereMonth('created_at', Carbon::now()->month)
            ->get();
        } else if ($timePeriod == 'year'){
            $topClients = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
            ->select('user_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('user_id')
            ->orderByRaw('visits DESC')
            ->limit(3)
            ->with('user:id,first_name,last_name,repitt_code')
            ->whereYear('created_at', Carbon::now()->year)
            ->get();
        } else { //Month as default
            $topClients = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
                })
            ->select('user_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('user_id')
            ->orderByRaw('visits DESC')
            ->limit(3)
            ->with('user:id,first_name,last_name,repitt_code')
            ->whereMonth('created_at', Carbon::now()->month)
            ->get();
        }



        return $topClients;
    }

    public function getVisitsByMonth($businessId){

        //Get the visits of the 12 months of the current year

        $visitsByMonth = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('COUNT(*) as visits'))
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->get();

        // Convert month names to Spanish
        $spanishMonths = [
            'January' => 'Enero',
            'February' => 'Febrero',
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];

        $visitsByMonth->transform(function ($item) use ($spanishMonths) {
            $item->month = $spanishMonths[$item->month];
            return $item;
        });

        return $visitsByMonth;
    }

    public function getMostVisitedMonth($businessId){

        //Get the month with the most visits of the current year

        $mostVisitedMonth = Visit::whereHas('stamp_card', function ($query) use ($businessId) {
            $query->where('business_id', $businessId);
            })
            ->select(DB::raw('MONTHNAME(created_at) as month'), DB::raw('COUNT(*) as visits'))
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderByRaw('visits DESC')
            ->first();

        // Convert month name to Spanish
        $spanishMonths = [
            'January' => 'Enero',
            'February' => 'Febrero',
            'March' => 'Marzo',
            'April' => 'Abril',
            'May' => 'Mayo',
            'June' => 'Junio',
            'July' => 'Julio',
            'August' => 'Agosto',
            'September' => 'Septiembre',
            'October' => 'Octubre',
            'November' => 'Noviembre',
            'December' => 'Diciembre'
        ];

        $mostVisitedMonth->month = $spanishMonths[$mostVisitedMonth->month];

        return $mostVisitedMonth;
    }

}
