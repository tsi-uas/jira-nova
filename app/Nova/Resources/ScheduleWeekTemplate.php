<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class ScheduleWeekTemplate extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Schedules';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduleWeekTemplate::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'display_name';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()
                ->sortable()
                ->onlyOnDetail(),

            Field::text('Display Name', 'display_name')
                ->sortable()
                ->rules('required', 'string', 'max:50'),

            Field::text('System Name', 'system_name')
                ->sortable()
                ->rules('nullable', 'string', 'max:50')
                ->creationRules('unique:schedule_week_templates,system_name')
                ->updateRules('unique:schedule_week_templates,system_name,{{resourceId}}'),

            Field::textarea('Description', 'description')
                ->hideFromIndex()
                ->rules('string', 'max:255'),

            Field::select('Due Date in Week', 'due_date_in_week')
                ->rules('required')
                ->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ]),

            Field::select('Allocation Type', 'allocation_type')
                ->rules('required')
                ->options([
                    'weekly' => 'Weekly',
                    'daily' => 'Daily'
                ]),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::dateTime('Deleted At', 'deleted_at')
                ->onlyOnDetail(),

            Field::hasMany('Day Templates', 'dayTemplates', ScheduleDayTemplate::class)

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
