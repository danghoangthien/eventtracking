<?php
namespace Hyper\EventBundle\Controller\Dashboard\Action;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\ConnectionObjectFields;
use FacebookAds\Object\Values\ConnectionObjectTypes;
use FacebookAds\Object\AdAccount;

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

use FacebookAds\Object\CustomAudience;
use FacebookAds\Object\Fields\CustomAudienceFields;
use FacebookAds\Object\Values\CustomAudienceTypes;
use FacebookAds\Object\Values\CustomAudienceSubtypes;

use FacebookAds\Object\AdsPixel;
use FacebookAds\Object\Fields\AdsPixelsFields;

// Doctrine
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Driver\PDOStatement;

class PushAudienceCardController extends Controller
{

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function pushToFacebookAction(Request $request)
    {
      $appId = $this->getParameter('facebook_app_id');
      $appSecret = $this->getParameter('facebook_app_secret');
      $accountAdsId = $this->getParameter('facebook_account_ads_id');
      
      $response = new Response();
      $response->headers->set('Content-Type', 'application/json');
      
      $fb = new Facebook(['app_id' => $appId,'app_secret' => $appSecret]);
      
      // Check login facebook
      $session = $request->getSession();
      if (!$session->has('facebook_access_token')) {
        $session->set('facebook_access_token', null);
      }
      if (!$session->get('facebook_access_token')) {
        $helper = $fb->getRedirectLoginHelper();
        try {
          $fbSession = (string) $helper->getAccessToken();
          $session->set('facebook_access_token', $fbSession);
        } catch(FacebookResponseException $e) {
          // When Graph returns an error
          return $response->setContent(json_encode([
            'status'=>false,
            'error'=>1,
            'content'=> 'Graph returned an error: ' . $e->getMessage()
          ]));
        } catch(FacebookSDKException $e) {
          // When validation fails or other local issues
          return $response->setContent(json_encode([
            'status'=>false,
            'error'=>1,
            'content'=> 'Facebook SDK returned an error: ' . $e->getMessage()
          ]));
        }
      } 
      if (!$session->get('facebook_access_token')) {
        $permissions = ['ads_management'];
        $url = $this->generateUrl('dashboard_client_action_push_audience_card', [], true);
        $loginUrl = $helper->getLoginUrl($url, $permissions);
        return $response->setContent(json_encode([
          'status'=>false,
          'error'=>1,
          'content'=> '<a href="'.$loginUrl.'">Please login with Facebook first!</a>'
          ]));
      }
      
      $data = $request->request->all();
      if (!isset($data['device_id'])) {
        $url = $this->generateUrl('dashboard_client_action_show');
        return $this->redirect($url);
      }
      
      $deviceIds = $data['device_id'];
      $strDeviceIds = '';
      foreach ($deviceIds as $deviceId) {
        $strDeviceIds .= "'".trim($deviceId)."',";
      }
      $strDeviceIds = substr($strDeviceIds, 0,-1);
      
      // $strDeviceIds = "'af0fcb22114f72685aed79b6a58101d0','1fb8d089cac9289c853e275acba86e49','01430bce3b922368a0a333507452727a','55d3f4b9a13b26.04797722','bc485fc66a7da57b8e885d229654f995','562806e163a388.28243845','3ddbdc1910a4976898fcd73f619e8e5c','09056033fb0a544fc854bdc8713ebdf5','1c885b0546e0e419c9b58756d67a2e15','e8471137feca7c8d3544dd4b1a7583d5','52226d0a4cf1f4e76fb5a1d6845ede42','560f9b1ade9069.71925185','224f277896a17b4106ad131611c76cf9','9a436ab8fbb5c34e47cd0536f5933583','58b08e0f0e5a83b3ae3a88a32645cf36','23cb2fbb77eda6103b86b9b9e3e7751e','dbf3a13a1f164062e45334e44e758742','2b0e44d905cbe22b61b07f80f6ecf1b5','5c60c306c90e735f2e607e76cc953d73','55e957c3bc4804.69471919','0606f2b386725f90f3969a394bc6877c','55996fa7a91de1e4c172c21c23b4af7c','55e4ad750302b4.37047434','63d7052c8d8d7b81edacd2f52186750a','64b608a6c79fd169fd638de81a9b5a7d','b2062079c8680fc7f9221a26ebac96cd','0ffbd04d46e5e068b851fe1e7aaae070','55e9635f816379.05161433','69fe30a79196b555eff8fd6e3a5d1fe7','ada9a74dff7e86f673fa296e6a3139ac','b3a9013128b4d09c6684e4e6b21bf1c6','561c0ee0bd5063.11916839','6fc9d259119939c7a350cef5c0d52d03','560f79437f8de3.08320898','55d356595e7152.65097699','146e67f7bfea725840b7f6b7d0ed525e','73919a500977097872dc8549f3f91346','8bb7a1988952ddc102ce00d229a238b1','68bd3ff3c8e64c225af5e86fde75e57e','857c75eb2e2032302a2ced09c41677e2','5887582914536596bbd88c69ea1777cc','8ab3d713b058ff6b7d05b3cc4bc8a9df','751d1b8691d8691f63c23e8b770b1dcb','8a67eb8d4576c2f3ceee85d5801d84c8','d08bc07067f097232db9d01b7ec50624','92f244efa5cd5035c8446fae44ebd49c','563a294f56c439.03818963','acece95c0ff51b879af835d95c1173d8','c885268922255a96aba0a6ee067329ba','55d66cd7922e49.70056275','8bac7c8b1a326fcb96a551962566fb50','55db39162a8b41.63976500','366ef2e66409decc6670a33d9f364cb1','57d350ec815f6a1de1483dbd92d9643c','76046d31b4964585c099bad0d5d957d9','960645230b1e1096db4ebfdd34eb1264','049e11dc83f9b026e16e530f8242159a','0d443939f7dc40fb0a4273a6e2ca782f','67f235395a9b224ac95f75a898b6f352','07e737a6860e399f80ac0d2d2e1a4a85','5627d0f79474a0.20102603','2650b3f0c81acee3dbcaa843eaf03ea8','3ba995c9133847d49b6314bd3144d09e','b1e47d79120580a069eee53c13690adf','4798d11b2c34d1174a46e911e085ccd8','589601b0706c50b6109c96ff73349ef1','1de1745f615d5a590df20527de98af07','d5d8dae00c1582a24df07cfca9e2ff4f','1de9630dd034fbd050b640ff84312631','4d1a739af35cdb084677b0a5f42dc65e','a8b8cdc099bf0e2597fbe254d2774fbd','e03a3fb50ad0bf8c0cf1fe7f0e8305d8','c7b4b708328b73fa551ebdd8df384337','55de4a5b2b69e5.27935883','fd5ae85c810fb76834b43ee6da766d60','e2367fff10beaf4b8e7baacf7d871af3','0d4460d189c113717de0f80c6327a11d','12bf554edd595bf2390231e64f4e99e6','6c1bd648b16d23fe7617a0faf7e8ea17','eaf1e395c26fa044f342fe10f10c570c','c8c80bc37cb04e4528e83e102debb620','b173120063ad4bb50a555c24e122de05','df7f6ce5ba2dd246987dffcfa9237c2d','88d3eb09e45e784bae334b5a8d2138e9','68939cf18df760d10433484498b8bc1d','f204aa830ffe4c9288d2d449ddadf427','b52ec4d8595fd6e4c129049c55906d19','9131454818c9790113965192ff5a1f32','9d34b853f8afc76a95fd80f67a6ea2e6','564b5151ecd036.30893514','fe68a8023981331f5d85bd7428de4927','7f8eda0ca30e9d5cdf58f88c2ea93db1','eeaeb8a5a1d1f413d8cfdd0e9d489550','bcfa5b285436c841b0d984a3d99085db','99dabf0eb1a7c9a393cbff4322be23c2','55d6880cb44410.93231566','56074410a8e625.67041732','701c39ebdfb08a4ef4beb2186185ad8a','de7f4784202688d9ce0300a1fe3b143a','2e1efccf8513174a930ad774bd8900d9','fe7a627beebe1e78ead40e439432834b','81a3e8b39753806269a8ab7b7a241902','55d76c271eba03.12782447','2a9fe3331b210c87783ff8ef99cad9a1','21a340ebf5dc03910def6717c5715860','09447bc54ef1799f5add84edc00139e3','447149e62d6edd2f1ebc59d13822f810','45d4a12542eb9b3cf7facf6bc5409893','7eedb425c084d4a34b1062989040f810','8f9a6557233bcdd52722301700a329b3','19bf271c422558ad324d4bc1a8fab3ea','89325b3e3905fcc3cf4ed1737acf2ff4','86e38e34ec3b9269c9309d467a18c60d','560ee2dd6564c0.84027247','55db1d18a25f47.34921486','227341fb77a4bac5d5cd9751a381e6aa','303c931b7004836eb7e46667c7a70d81','776bb560fe4aea4a4c5560ef6dc5635b','6313b366cd11370af6700585a81e43f4','18a623490531be1a33c792e5e78e4699','068a148f51d52fb68e84ff7fb3aca718','562a7d95e5dfd5.18303421','edc66502ad30d48df9ab9135d608ea4a','5332eb05f2f2d7e69a0f6902b5442a83','67c0706333da6dfdb3e42a438f93fa12','56090ca73b68e0.73607783','0f97647d7ac764e69ef0f1ced470aa34','dd986f967c58c8689ad0ca61cc7e9a19','94533bf07e3cd9624f15e2105aed52a9','bff1dd838d3cc316cfa68ad6ef75b040','7f0d1506d3ea47efb60a363a70a2a829','93b5db9705c5231a7e5cef3341576736','7df485d073c60e2d844b4148c16eeb9c','b3eb59632f300729019935f2729ad8fd','8d0f3dd0b28faf8cb9b31cc5b4cc5b97','6a93453d770d3273c292ce59b41c1939','55e79b2fc881c8.46344107','562ae00e9ab384.93923885','88aa7f8591ff67a665f630fd96a7ad8a','e817597b94073b74f8f6d2044e8851c5','af67c2e7360ded81460bee9f1003df94','235df9c6ce3dba79062043412d56f042','55ddae77197963.41926352','3fe72bbc26221dd06bcc539cadda097e','870c226f921fc734822606f691de049f','55d627b624b983.33965842','32acfb6f384ddb498a1432edb096be9b','55e66590180185.30083552','55e727e9d7bc07.13965081','55f56d9835f0d3.54636941','30ee5837dce8991d70ea7d5e69c56a22','b09bf0356d657f5320e895e068c249af','55dbb569958f25.04511040','6531c4578ccb0c211565f2e711f5ef13','561509f6a4e682.82570207','7f702107f015c91e65eeb70365b40805','861846b9f3c2f4bce04ba6b07cb347e3','4a201d70f81e09daef492228151fde9e','562ea410eeae91.14065293','55da286677bc25.63754493','a09861bf191b8c16e583f9b9bb78968d','74189dd3054555ba2a9d6dcb017245a4','55f6d6af473b83.10049111','3efdf2f3c7f75420328a1eb52b048b0a','c97932a1a3caafc2be7d5cbbe634657f','5604d2b2962a14.62541180','cfa5e3a62a21b00b4978126e54669833','55d37b1e1c9f23.11596022','55d9e2fe323759.13200830','e5f4bf9215021d1298fd045bb0d6a791','dd0adf4f71b71ed8945d996e3132e2dd','5634bbf5a518b1.46980340','75a51c9417d1a3a402c52057ff6a67de','de0171c37e13f5f7351848befd5f92f3','55f6cdee904a43.04052579','dc2e19c0d5c9ca8006118162a9956daf','55d2d5b066f128.83739528','dc7e716f923e270086c571aca97f2e70','640da7b1ed7ef753295d676e2d750a3a','c6d0ef87092a40e4790774c71685a268','4dbd0bdceaa55935025d81d6966d8625','4ef4eda7ac947e3424074935a4bc3f89','695408b71be9aa0784bf9e4bc3e95dc7','55d38524c63778.20740399','c1d6b4bc30893169ced91ddb1bd148f4','c950400e3d9a7b12aa49aef739fc47fa','ee442252941ef6dce0ad8a575174fc9c','a629a36f0e616ac369e059a78bc79e33','22442a51ea9f7f9038eff36d655447d6','6b67cc8a96922114366575dfb8ea4457','4e1c5f1c840cbc5e4d439b2e7f1e722e','26e88fa7043bf2eb5ffeda1e5d5d5a04','465073ad1d4c18222ebe37c161e7d878','05214c1a0a93a56dd30e7389d1747887','7d108cb09eb6c2d72425c5c977137f35','bad85fa86e0d1d0aedb4f0a53f2775ab','88ba2a42b4af2c6c28bdf8033fa01541','c30541ab60d1221b8e85f2f57ae6802e','55e12d0398acf7.99650628','f1257465c258056f720cccff5a372bd1','47757c1a06d8c73d4908befa43b6f696','563ff27bb5af90.37151590','a108cd0e303ddc5571c2d84cbe7a7760','f65569a7464ffc4383782f58027d057f','55f1d0dc5db836.36937803','5617e31d3584b2.02128461','eadb703dc63bb588790bf571d0846a15','ee981eeb1c278a48d775aa175d7c83ff','b902a0c63cb59eb54ef6764fefaef674','db48b7bfb9c639524708943e4c104bfd','55e2c9a3552c87.15628338','55e21a8530f199.31337656','234e99a3317d0ca27daff6399f4eaaa6','55deae6f4bef74.88044551','c5271cfa1cdff5411da58804d987e849','ab03292f8bb9b7a56dd1d0c5af1e268c','562c307c51dac7.23194105','cdb2c1a98fd45ab2befccdd7a6136ab5'";
      $accessToken = $session->get('facebook_access_token');
      Api::init($appId, $appSecret, $accessToken);
      
      /*$cSql = $conn->prepare("SELECT ios_devices.id, idfa, idfv,devices.country_code 
FROM ios_devices INNER JOIN devices ON devices.id = ios_devices.id 
WHERE ios_devices.id IN (SELECT distinct(actions.device_id) as device_id FROM applications INNER JOIN actions ON applications.app_id = actions.app_id INNER JOIN devices ON actions.device_id = devices.id WHERE actions.app_id = 'id935189878' and actions.behaviour_id = '4' and devices.country_code in ('CA','AU','PH') GROUP BY actions.device_id ) GROUP BY ios_devices.id, idfa, idfv,devices.country_code;
");*/
      $listDeviceIds = [];
      $conn = $this->get('doctrine.dbal.pgsql_connection');
      // Get idfa from ios_devices
      $cSql = $conn->prepare("SELECT distinct(idfa) FROM ios_devices WHERE ios_devices.id IN ($strDeviceIds)");
      $cSql->execute();
      for($x = 0; $row = $cSql->fetch(); $x++) {
        if (!empty(trim($row['idfa']))) {
          $listDeviceIds[] = $row['idfa'];
        }
      }
      
      //Get advertising_id from android_devices
      $cSql = $conn->prepare("SELECT distinct(advertising_id) FROM android_devices WHERE android_devices.id IN ($strDeviceIds)");
      $cSql->execute();
      for($x = 0; $row = $cSql->fetch(); $x++) {
        if (!empty(trim($row['advertising_id']))) {
          $listDeviceIds[] = $row['advertising_id'];
        }
      }

      // Create a custom audience object, setting the parent to be the account id
      $audience = new CustomAudience(null, $accountAdsId);
      $audience->setData(array(
        CustomAudienceFields::NAME => 'ListDeviceID_'.time(),
        CustomAudienceFields::DESCRIPTION => 'Lots of device ids',
        CustomAudienceFields::SUBTYPE => CustomAudienceSubtypes::CUSTOM,
      ));

      // Create the audience
      $audience->create();
      $audience->addUsers($listDeviceIds, CustomAudienceTypes::MOBILE_ADVERTISER_ID);
      $audience->read(array(CustomAudienceFields::APPROXIMATE_COUNT));
      
      $output = [
        'status' => true,
        'audience_id' => $audience->id,
        'estimated_size' => $audience->{CustomAudienceFields::APPROXIMATE_COUNT},
        'count_device' => count($listDeviceIds)
      ];
        
      return $response->setContent(json_encode($output));
    }
}