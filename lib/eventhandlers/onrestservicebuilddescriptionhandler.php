<?

namespace Local\Refreshnews\EventHandlers;
//\inq\sofbit.crmtools\classes\general\CrmProductTools.php cansiple

use Bitrix\Main\Localization;
use Bitrix\Rest\RestException;
use CRestServer;
use Local\Refreshnews\Randominf\Rest;

Localization\Loc::loadMessages(__FILE__);

class OnRestServiceBuildDescriptionHandler
{

	static public function myfunc()
	{
		die(__FILE__ . ":" . __LINE__ . "\n\n");
	}

	/**
	 * Method for EventHandler module REST
	 * @return array
	 */
	public static function OnRestServiceBuildDescription()
	{
		return array(
			'refreshnews' => array(
				'news.get' => array(
					'callback' => array(__CLASS__, 'newsGet'),
					'params' => array()
				),
				'news.stat' => array(
					'callback' => array(__CLASS__, 'newsStat'),
					'params' => array()
				)
			)
		);
	}

	static public function handler($arFields = [])
	{
		/*todo instancccea*/
	}


	public static function newsGet($query, $n, CRestServer $server)
	{
		if ($query['error']) {
			throw new RestException(
				'Message',
				'ERROR_CODE',
				CRestServer::STATUS_PAYMENT_REQUIRED
			);
			return array('error' => $query);
		}

		return array('uquery' => $query, 'mynewsget' => 'My own response');


	}

	public static function newsStat($query, $n, CRestServer $server)
	{
		if ($query['error']) {
			throw new RestException(
				'Message',
				'ERROR_CODE',
				CRestServer::STATUS_PAYMENT_REQUIRED
			);
			return array('error' => $query);
		}
		return array('uquery' => $query, 'newsStat' => 'My own response');
	}

}
