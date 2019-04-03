<?php

namespace App\Models;

use Jira;
use Cache;
use JiraRestApi\Project\Project as JiraProject;

class Project extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jira_id', 'jira_key'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'issues_synched_at'
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        // Call the parent method
        parent::boot();

        // When creating this model...
        static::creating(function($model) {

            // Sync from jira
            $model->updateFromJira();

        });
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\User\User|null
     *
     * @return $this
     */
    public function updateFromJira(JiraProject $jira = null)
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira) {

            // If a jira user wasn't specified, find it
            $jira = $jira ?: $this->jira();

            // Assign the attributes from jira
            $this->syncAttributesFromJira($jira);

            // Save
            $this->save();

            // Sync the related entities
            $this->syncLeadFromJira($jira);
            $this->syncComponentsFromJira($jira);
            $this->syncIssueTypesFromJira($jira);
            $this->syncVersionsFromJira($jira);

            // Allow chaining
            return $this;

        });
    }

    /**
     * Syncs this attributes from jira.
     *
     * @param  \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncAttributesFromJira(JiraProject $jira)
    {
        $this->jira_id = $jira->id;
        $this->jira_key = $jira->key;
        $this->display_name = $jira->name;
    }

    /**
     * Syncs the project lead from jira.
     *
     * @param  \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncLeadFromJira(JiraProject $jira)
    {
        // Determine the project lead
        $lead = $jira->lead;

        // Determine the account id
        $accountId = $lead['accountId'];

        // Create or update the user from jira
        User::createOrUpdateFromJira(compact('accountId'));
    }

    /**
     * Syncs the project components from jira.
     *
     * @param  \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncComponentsFromJira(JiraProject $jira)
    {
        // Determine the project components
        $components = $jira->components;

        // Create or update the components from jira
        foreach($components as $component) {

            // Add the project attributes to the component
            $component->project = $this->jira_key;
            $component->projectId = $this->jiraId;

            // Create or update each component
            Component::createOrUpdateFromJira($component, [
                'project' => $this
            ]);

        }
    }

    /**
     * Syncs the issue types from jira.
     *
     * @param  \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncIssueTypesFromJira(JiraProject $jira)
    {
        // Determine the issue types
        $issueTypes = $jira->issueTypes;

        // Create or update the issue types from jira
        foreach($issueTypes as $issueType) {
            IssueType::createOrUpdateFromJira($issueType);
        }
    }

    /**
     * Syncs the project versions from jira.
     *
     * @param  \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncVersionsFromJira(JiraProject $jira)
    {
        // Determine the versions
        $versions = $jira->versions;

        // Create or update the versions from jira
        foreach($versions as $version) {

            // Create or update each version
            Version::createOrUpdateFromJira($version, [
                'project' => $this
            ]);

        }
    }

    /**
     * Syncs the project issues from jira.
     *
     * @return void
     */
    public function syncIssuesFromJira()
    {
        // Determine the issues from jira
        $issues = $this->getUpdatedJiraIssues();

        // Create or update the issues from jira
        foreach($issues as $issue) {

            // Create or update each issue
            Issue::createOrUpdateFromJira($issue, [
                'project' => $this
            ]);

        }
    }

    /**
     * Returns the jira issues that have been updated since the project was last synched.
     *
     * @return array
     */
    public function getUpdatedJiraIssues()
    {
        // Determine the search query
        $jql = $this->newUpdatedJiraIssuesExpression();

        // Initialize the list of issues
        $issues = [];

        // Initialize the pagination variables
        $page = 0;
        $count = 50;

        // Loop until we're out of results
        do {

            // Determine the search results
            $results = Jira::issues()->search($jql, $page * $count, $count, [
                'summary',
                'description',
                'created',
                'updated'
            ], [], false);

            // Determine the number of results
            $countResults = count($results->issues);

            // If there aren't any results, stop here
            if($countResults == 0) {
                break;
            }

            // Append the results to the list of issues
            $issues = array_merge($issues, $results->issues);

            // Forget the results
            unset($results);

            // Increase the page count
            $page++;

        } while ($countResults == $count);

        // Return the list of issues
        return $issues;
    }

    /**
     * Creates and returns a new updated jira issues expression.
     *
     * @return string
     */
    public function newUpdatedJiraIssuesExpression()
    {
        // If this project hasn't ever been synched, load all issues
        if(is_null($this->issues_synched_at)) {
            return "project = {$this->jira_key}";
        }

        // Otherwise, load issues that have been updated since the project was synched
        return "project = {$this->jira->key} and updated >= {$this->issues_synched_at}";
    }

    /**
     * Finds and returns the specified jira project.
     *
     * @param  array  $attributes
     *
     * @return \JiraRestApi\Project\Project|null
     */
    public static function findJira($attributes = [])
    {
        // Return the result for a set interval
        return static::getJiraCache()->remember(static::class . ':' . json_encode($attributes), 15 * 60, function() use ($attributes) {

            // Check for a project id
            if(isset($attributes['project_id'])) {
                return Jira::projects()->get($attributes['project_id']);
            }

            // Check for a project key
            if(isset($attributes['project_key'])) {
                return Jira::projects()->get($attributes['project_key']);
            }

            // Unknown project
            return null;

        });
    }

    /**
     * Returns the jira project for this project.
     *
     * @return \JiraRestApi\User\User
     */
    public function jira()
    {
        return static::findJira([
            'project_id' => $this->jira_id,
            'project_key' => $this->jira_key
        ]);
    }

    /**
     * Returns the jira cache.
     *
     * @return \Illuminate\Cache\Repository
     */
    public static function getJiraCache()
    {
        return Cache::store('jira');
    }
}
