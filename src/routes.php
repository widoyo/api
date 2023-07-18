<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/device[/{sn}]', function (Request $req, Response $res, array $args) use ($container) {
        if (! isset($args['sn'])) {
            $device = [];
            foreach ($this->db->query(
                "SELECT sn, tipe, nama FROM device "
                . "LEFT JOIN lokasi ON lokasi_id = lokasi.id "
                . "ORDER BY sn")
                ->fetchAll() as $d) {
                $device[] = array( 
                    "device_id" => $d['sn'], 
                    "type" => $d['tipe'],
                    "location" => $d['nama']
                 );
            }
            return $res->withJson( array( "result" => $device ), 200 );
        } else {
            $device = $this->db->query( "SELECT sn, tipe, nama FROM device "
                . "LEFT JOIN lokasi ON lokasi_id=lokasi.id "
                . "WHERE sn='{$args['sn']}'" )
                ->fetch();
            
            if (! $device ) {
                return $res->withJson(array( "status" => 404, "message" => "Not found" ));
            }

            $periodik = [];
            $basic_info = array( "device_id" => $args['sn'], "station" => $device['nama'],
        "timezone" => "WITA" );

            if ( $device['tipe'] == 'arr' ) {
                foreach( $this->db->query(
                    "SELECT sampling, rain FROM periodik "
                    . "WHERE device_sn='{$args['sn']}' "
                    . "ORDER BY sampling DESC LIMIT 288")->fetchall() as $p) {
                        $this_row = array( "reading_at" => $p['sampling'], 
                        "rainfall" => $p['rain'] | 0,
                        "type" => "Pos Curah Hujan"
                     );
                        $periodik[] = array_merge($basic_info, $this_row);
                    }
    
            } else if ( $device['tipe'] == 'awlr' ) {
                foreach( $this->db->query(
                    "SELECT sampling, wlev FROM periodik "
                    . "WHERE device_sn='{$args['sn']}' "
                    . "ORDER BY sampling DESC LIMIT 288")->fetchall() as $p) {
                        $this_row = array( "reading_at" => $p['sampling'], 
                        "tma" => $p['wlev'] | 0,
                        "unit" => "cm",
                        "type" => "Pos Duga Air"
                     );
                        $periodik[] = array_merge($basic_info, $this_row);
                    }

            }

            return $res->withJson( array( "metaData" => array( "code" => 200, "message" => "OK"), "response" => $periodik ) );
        }
        
    });

    $app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
        // Sample log message
        $container->get('logger')->info("Slim-Skeleton '/' route");

        // Render index view
        return $container->get('renderer')->render($response, 'index.phtml', $args);
    });
};
