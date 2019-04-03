<?php

namespace App\Support\Jira;

use JiraRestApi\User\UserService;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\Priority\PriorityService;

class JiraService
{
	/**
	 * Returns the issue service.
	 *
	 * @return \JiraRestApi\User\IssueService
	 */
	public function issues()
	{
		return new IssueService;
	}

	/**
	 * Returns the priorty service.
	 *
	 * @return \JiraRestApi\Priority\PriorityService
	 */
	public function priorities()
	{
		return new PriorityService;
	}

	/**
	 * Returns the project service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function projects()
	{
		return new ProjectService;
	}

	/**
	 * Returns the user service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function users()
	{
		return new UserService;
	}
}