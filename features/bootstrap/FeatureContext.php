<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
	protected $response = null;
	protected $username = null;
	protected $password = null;
	protected $client = null;
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    /**
     * public function __construct($github_username, $github_password)
    {
    */
    public function __construct($github_username, $github_password)
    {
	    $this->username = $github_username;
	    $this->password = $github_password;
	    
	    echo("username and password from behat.yml " . $this->username . " " . $this->password);
	    $this->username = null;
	    $this->password = null;
	    $this->username = 'PhilNorman2';
	    $this->password = '';
	    echo("\nusername and password" . $this->username . " " . $this->password);
    }
  /**
   * @Given I am an anonymous user
   */
  public function iAmAnAnonymousUser()
  {
     return true; 
  }

  
  /**
   * @When I search for :arg1
   */
  public function iSearchFor($arg1)
  {
	  $client = new GuzzleHttp\Client(['base_uri' => 'https://api.github.com']);
	  $this->response = $client->get('/search/repositories?q=' . $arg1);
  }

   /**
  * @Then I expect a :arg1 response code
  */
 public function iExpectAResponseCode($arg1)
 {
	  $response_code = $this->response->getStatusCode();
	  if ($response_code <> $arg1) {
      		throw new Exception("mesage: It didn't work We expected a " . $arg1 . "response.  We got ".$response_code );
	}
	 
 }

   /**
   * @Then I expect at least :arg1 result
   */
  public function iExpectAtLeastResult($arg1)
 {
	  $data = json_decode($this->response->getBody(), true);
	  if ($data['total_count'] < $arg1) {
      		throw new Exception("mesage: We expected " . $arg1 . "results but found". $data['total_count'] . "results");
	}	
 }

    /**
     * @Given I am an authenticated user
     */
    public function iAmAnAuthenticatedUser()
    {
	    echo " " . $this->username . " " . $this->password;

	    $this->client = new GuzzleHttp\Client([
		    'base_uri' => 'https://api.github.com',
		     'auth' => [$this->username, $this->password]
	    ]);
	    $this->response = $this->client->get('/');

	    $this->iExpectAResponseCode(200);

    }

    /**
     * @When I request a list of my repositories
     */
    public function iRequestAListOfMyRepositories()
    {
	    $this->response = $this->client->get('/user/repos');
	    $this->iExpectAResponseCode(200);

    }


    /**
     * @Then The results should include a repository name :arg1
     */
    public function theResultsShouldIncludeARepositoryName($arg1)
    {
	    $repositories = json_decode($this->response->getBody(),true);
	    foreach($repositories as $repository) {
		    echo ' ' . $arg1 . '\n';
		    if ($repository['name'] == $arg1) {
			    return true;
		    }
            }
        throw new Exception("Could not find" . $arg1);
    }

    /**
     * @When I create the :arg1 repository
     */
    public function iCreateTheRepository($arg1)
    {
	$parameters = json_encode(['name' => $arg1]);
	$this->client->post('/user/repos', ['body' => $parameters]);
	$this->iExpectAResponseCode(200);
    }
    
   /**
   * @Given I have a repository called :arg1
   */
  public function iHaveARepositoryCalled($arg1)
  {
    $this->iRequestAListOfMyRepositories();
    $this->theResultsShouldIncludeARepositoryName($arg1);

  }

  /**
   * @When I watch the :arg1 repository
   */
  public function iWatchTheRepository($arg1)
  {
	$parameters = json_encode(['subscribed' => true]);
	$this->response = $this->client->put('/repos/' . $this->username . '/' . $arg1 . '/subscription', ['body' => $parameters]);
	$this->iExpectAResponseCode(200);
      
  }

  /**
   * @Then The :arg1 repository will list me as a watcher
   */
  public function theRepositoryWillListMeAsAWatcher2($arg1)
  {
      $this->response = $this->client->get('/repos/' . $this->username . '/' . $arg1 . '/subscription');
      $this->iExpectAResponseCode(200);
     var_dump($this->response); 

  }

  /**
   * @Then I delete the repository called :arg1
   */
  public function iDeleteTheRepositoryCalled($arg1)
  {
	$this->response = $this->client->delete('/repos/' . $this->username . '/' . $arg1);
      $this->iExpectAResponseCode(204);
  }

  protected function iExpectASuccessfulRequest()
  {

  }

  protected function iExpectAFailedRequest()
  {

  }

/**
     * @Given I have the following repositories:
     */
    public function iHaveTheFollowingRepositories(TableNode $table)
    {
	    $this->table = $table->getRows();
	    array_shift($this->table);
	    foreach($this->table as $id => $row) {
		    $this->table[$id]['name'] = $row[0] . '/' . $row[1];
		    $this->response = $this->client->get('/repos/'.$row[0].'/'.$row[1]);
		    $this->iExpectAResponseCode(200);
	    }

    }

    /**
     * @When I watch each repository
     */
    public function iWatchEachRepository()
    {
	    $parameters = json_encode(['subscribed' => 'true']);

	    foreach($this->table as $row) {
		    $watch_url = '/repos/' . $row['name'] . '/subscription';
		    $this->client->put($watch_url, ['body' => $parameters]);
	    }
    }

    /**
     * @Then My watch list will include those respositories
     */
    public function myWatchListWillIncludeThoseRespositories()
    {
	    $watch_url = '/users/' . $this->username . '/subscriptions';
	    $this->response = $this->client->get($watch_url);
	    $watches = json_decode($this->response->getBody(), true);
	    var_dump($watches);

	    foreach ($this->table as $row) {
		    $fullname = $row['name'];

		    foreach ($watches as $watch) {
		    	if ($fullname == $watch['full_name']) {
				break 2;
			}
		    }

		    throw new Exception("Error!" . $this->username . " is not watching"  . $fullname);

	   } 
    }

    /**
     * @Then I delete all of my watches
     */
    public function iDeleteAllOfMyWatches()
    {

	     foreach($this->table as $row) {
	            $this->response = $this->client->delete('/repos/' . $row['name'] . '/subscription');
                    $this->iExpectAResponseCode(204);
	    }
    }
    
}
