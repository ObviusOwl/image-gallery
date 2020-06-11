<?php 
namespace ImageGallery;

class Database{
    protected $conf = null;
    protected $dbh = null;
    protected $browser = null;
    
    public function __construct(Config $conf){
        $this->conf = $conf;

        $dsn = "mysql:dbname=" . $this->conf["app.db.dbname"] . ";host=" . $this->conf["app.db.host"];
        $opts = [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
            \PDO::ATTR_ERRMODE            =>  \PDO::ERRMODE_EXCEPTION,
        ];

        try {
            $this->dbh = new \PDO($dsn, $this->conf["app.db.user"], $this->conf["app.db.password"], $opts);
        }catch( \PDOException $e ){
            throw new DatabaseError("Error connecting to database", 0, $e);
        }
        
        $this->browser = new FileBrowser($this->conf, $this);
    }
    
    protected function beginTransaction(){
        $this->dbh->beginTransaction();
    }
    
    protected function rollBack(){
        $this->dbh->rollBack();
    }
    
    protected function commit(){
        $this->dbh->commit();
    }
    
    public function constructGallery($id, $name, $path){
        $ga = new Gallery();
        $ga->setId(intval($id));
        $ga->setVirtualPath(new Path($path));
        $ga->setName($name);
        $ga->clearTaint();
        return $ga;
    }
    
    public function constructFile($id, $path, $name, $descr, $type, $galleryId){
        // TODO set description
        $file = $this->browser->fileFactory(new Path($path), $type);
        $file->setName($name);
        if( $file instanceof ImageFile ){
            $file->setId($id !== null ? intval($id) : null);
            $file->setType($type);
        }else if( $file instanceof GalleryFile ){
            $file->setId($id !== null ? intval($id) : null);
            $file->setGalleryId($galleryId !== null ? intval($galleryId) : null);
        }
        $file->clearTaint();
        return $file;
    }
    
    protected function requireGalleryId(Gallery $gallery){
        $id = $gallery->getId();
        if( $id === null ){
            throw new DatabaseError("gallery id must be set");
        }
        return $id;
    }

    protected function requireFileId(File $file){
        $id = $file->getId();
        if( $id === null ){
            throw new DatabaseError("file id must be set");
        }
        return $id;
    }
    
    protected function queryGalleries(string $sql, array $params){
        try{
            $sth = $this->dbh->prepare($sql);
            $sth->execute($params);
            return $sth->fetchAll(\PDO::FETCH_FUNC, [$this, "constructGallery"]);
        }catch( \PDOException $e ){
            throw new DatabaseError("Failed to query gallery from database", 0, $e);
        }
    }

    protected function queryFiles(string $sql, array $params){
        try{
            $sth = $this->dbh->prepare($sql);
            $sth->execute($params);
            return $sth->fetchAll(\PDO::FETCH_FUNC, [$this, "constructFile"]);
        }catch( \PDOException $e ){
            throw new DatabaseError("Failed to query file from database", 0, $e);
        }
    }
    
    public function galleryLoadFiles(Gallery $gallery){
        $this->requireGalleryId($gallery);

        $q="SELECT 
                files.id, files.path, files.name, files.description, files.type, files.gallery_id 
            FROM files 
            JOIN 
                gallery_items ON gallery_items.file_id = files.id 
            WHERE 
                gallery_items.gallery_id = :id
            ORDER BY gallery_items.position ASC
        ";
        $files = $this->queryFiles($q, [ ":id"=>$gallery->getId() ]);
        $gallery->setFiles(...$files);

        foreach( $gallery->getFiles() as $file ){
            if( $file instanceof GalleryFile ){
                $this->galleryFileLoad($file);
            }
        }
        return $gallery;
    }
    
    public function galleryLoadThumbnails(Gallery $gallery){
        $this->requireGalleryId($gallery);
        
        $q="SELECT 
                files.id, files.path, files.name, files.description, files.type, files.gallery_id 
            FROM files 
            JOIN 
                gallery_thumbnails ON gallery_thumbnails.file_id = files.id 
            WHERE 
                files.type LIKE 'image/%' 
                AND gallery_thumbnails.gallery_id = :id
            ORDER BY gallery_thumbnails.position ASC
        ";
        $files = $this->queryFiles($q, [ ":id"=>$gallery->getId() ]);
        $gallery->setThumbnails(...$files);
        return $gallery;
    }
    
    public function getGalleryById(int $id){
        $sql = "SELECT id, name, path FROM galleries WHERE id = :value";
        $galleries = $this->queryGalleries($sql, [ ":value" => $id ]);
        return count($galleries) == 0 ? null : $galleries[0];
    }
    
    public function getGalleryByPath(Path $path){
        $path = $path->resolve()->makeAbsolute();
        $q="SELECT galleries.id, galleries.name, galleries.path
            FROM galleries
            JOIN files ON files.gallery_id = galleries.id
            WHERE galleries.path like :path or files.path like :path
        ";
        
        $galleries = $this->queryGalleries($q, [ ":path" => strval($path) ]);
        return count($galleries) == 0 ? null : $galleries[0];
    }
    
    public function addGallery(Gallery $gallery){
        $this->beginTransaction();
        try{
            $q = "INSERT INTO galleries (name, path) VALUES (:name, :path)";
            $sth = $this->dbh->prepare($q);
            $sth->execute([ 
                ":name" => $gallery->getName(),
                ":path" => strval($gallery->getVirtualPath()),
            ]);
            $gallery->setId($this->dbh->lastInsertId());
            
            $files = $gallery->getFiles();
            $q = "INSERT INTO gallery_items (gallery_id, file_id, position) VALUES (:gid, :fid, :pos)";
            $sth = $this->dbh->prepare($q);
            for( $p=0; $p < count($files); $p++ ){
                $data = [ ":gid" => $gallery->getId(), ":fid" => $files[$p]->getId(), ":pos" => $p ];
                $sth->execute($data);
            }
        }catch( \PDOException $e ){
            $this->rollBack();
            throw new DatabaseError("Failed to set gallery thumbnails", 0, $e);
        }
        $this->commit();
    }
    
    public function updateGallery(Gallery $gallery){
        $this->requireGalleryId($gallery);

        $taintGetter = [
            "name" => function ($g){ return $g->getName(); },
            "virtualPath" => function ($g){ return strval($g->getVirtualPath()); },
        ];
        $taintAttrMap = [
            "name" => "name",
            "virtualPath" => "path",
        ];
        
        $tainted = $gallery->getTainted();
        $params = [];
        $setStm = [];
        
        foreach( $taintAttrMap as $taint => $attr ){
            if( isset($tainted[$taint]) ){
                $params[ ":$attr" ] = $taintGetter[$taint]($gallery);
                $setStm[] = "$attr = :$attr";
            }
        }
        
        if( count($setStm) == 0 ){
            return;
        }
        
        $qset = implode(', ', $setStm);
        $q = "UPDATE galleries SET $qset WHERE id = :id";
        $params[":id"] = $gallery->getId();
        
        $sth = $this->dbh->prepare($q);
        $sth->execute($params);
    }
    
    public function gallerySetThumbnails(Gallery $gallery, int ...$fileIds){
        $gid = $this->requireGalleryId($gallery);
        
        $this->beginTransaction();
        try{
            $sth = $this->dbh->prepare("DELETE FROM gallery_thumbnails WHERE gallery_id = :id");
            $sth->execute([ ":id" => $gid ]);

            $q = "INSERT INTO gallery_thumbnails (gallery_id, file_id, position) VALUES (:gid, :fid, :pos)";
            $sth = $this->dbh->prepare($q);
            for( $p=0; $p < count($fileIds); $p++ ){
                $sth->execute([ ":gid" => $gid, ":fid" => $fileIds[$p], ":pos" => $p ]);
            }
        }catch( \PDOException $e ){
            $this->rollBack();
            throw new DatabaseError("Failed to set gallery thumbnails", 0, $e);
        }
        $this->commit();
    }
    
    public function gallerySetFiles(Gallery $gallery, int ...$fileIds){
        $gid = $this->requireGalleryId($gallery);

        $new = [];
        for( $p=0; $p < count($fileIds); $p++ ){
            $new[] = [ $fileIds[$p], $p ];
        }

        $this->beginTransaction();
        try{
            $q="SELECT file_id,position FROM gallery_items WHERE gallery_id = :gid ORDER BY position ASC";
            $sth = $this->dbh->prepare($q);
            $sth->execute([ ":gid" => $gid ]);
            $old = $sth->fetchAll(\PDO::FETCH_NUM);
            $old = array_map(function ($r){ return [ intval($r[0]), intval($r[1]) ]; }, $old);

            $diff = [];
            
            $maxIdx = max(count($old), count($new));
            for( $p=0; $p < $maxIdx; $p++ ){
                if( ! isset($old[$p]) ){
                    $diff[] = [ null, $new[$p] ];
                }else if( ! isset($new[$p]) ){
                    $diff[] = [ $old[$p], null ];
                }else if( $old[$p] !== $new[$p] ){
                    $diff[] = [ $old[$p], $new[$p] ];
                }
            }

            $q="DELETE FROM gallery_items WHERE gallery_id = :gid AND file_id = :fid AND position = :pos";
            $delQuery = $this->dbh->prepare($q);
            
            $q="INSERT INTO gallery_items (gallery_id, file_id, position) VALUES (:gid, :fid, :pos)";
            $insQuery = $this->dbh->prepare($q);
            
            foreach( $diff as $d ){
                if( $d[0] !== null ){
                    $delQuery->execute([ ":gid" => $gid, ":fid" => $d[0][0], ":pos" => $d[0][1] ]);
                }
                if( $d[1] !== null ){
                    $insQuery->execute([ ":gid" => $gid, ":fid" => $d[1][0], ":pos" => $d[1][1] ]);
                }
            }

        }catch( \PDOException $e ){
            $this->rollBack();
            throw new DatabaseError("Failed to set gallery files", 0, $e);
        }
        $this->commit();

        $this->galleryLoadFiles($gallery);
    }
    
    public function deleteGallery(int $id){
        try{
            $q="DELETE FROM galleries WHERE id = :gid";
            $sth = $this->dbh->prepare($q);
            $sth->execute([ ":gid" => $id ]);
        }catch( \PDOException $e ){
            throw new DatabaseError("Failed to delete gallery", 0, $e);
        }
    }
    
    public function getFileById(int $id){
        $q="SELECT id, path, name, description, type, gallery_id
            FROM files
            WHERE id = :id
        ";
        $files = $this->queryFiles($q, [ ":id" => $id ]);
        return count($files) == 0 ? null : $files[0];
    }
    
    public function browseGalleryFile(Path $path){
        // may return galleryfiles with NULL id
        $path = $path->resolve()->makeAbsolute();
        $q="SELECT files.id, files.path, files.name, files.description, files.type, files.gallery_id 
            FROM files
            WHERE files.gallery_id IS NOT NULL AND files.path LIKE :path
            
            UNION ALL
            SELECT NULL, galleries.path, galleries.name, NULL, 'application/x.image-gallery', galleries.id 
            FROM galleries
            WHERE galleries.path LIKE :path
        ";
        $files = $this->queryFiles($q, [ ":path" => strval($path) ]);
        return count($files) == 0 ? null : $files[0];
    }
    
    public function browseGalleryFiledir(Path $path){
        // may return galleryfiles with NULL id
        $path = preg_quote(strval($path->resolve()->makeAbsolute()), "&");
        $data = [
            ":file_reg" => "^$path/[^/]+$", 
            ":dir_reg" =>  "^$path/[^/]+/", 
            ":dir_reg2" => "^$path/[^/]+",
        ];
        
        $q="SELECT DISTINCT * FROM (
        	SELECT files.id, files.path, files.name, files.description, files.type, files.gallery_id 
            FROM files
            WHERE files.gallery_id IS NOT NULL AND files.path REGEXP :file_reg
            
        	UNION ALL
        	SELECT NULL, galleries.path, galleries.name, NULL, 'application/x.image-gallery', galleries.id 
            FROM galleries
            WHERE galleries.path REGEXP :file_reg

            UNION ALL
        	SELECT NULL, REGEXP_SUBSTR(files.path, :dir_reg2), NULL, NULL, 'inode/directory', NULL
            FROM files
            where files.gallery_id is not NULL and files.path regexp :dir_reg

            UNION ALL
        	SELECT NULL, REGEXP_SUBSTR(galleries.path, :dir_reg2), NULL, NULL, 'inode/directory', NULL 
        	FROM galleries
            WHERE galleries.path REGEXP :dir_reg

        ) AS dirlist GROUP BY path
        ";
        
        $files = $this->queryFiles($q, $data);
        return $files;
    }
    
    public function getOrCreateFile(File $file){
        if( $file->getId() !== null ){
            return $this->getFileById($file->getId());
        }else if( $file->getVirtualPath() !== null ){
            $path = strval($file->getVirtualPath()->makeAbsolute());
        }else{
            throw new DatabaseError("File id or virtualPath must be set");
        }
        
        $q="SELECT id, path, name, description, type, gallery_id
            FROM files
            WHERE path LIKE :path
        ";
        $files = $this->queryFiles($q, [ ":path" => $path ]);
        if( count($files) !== 0 ){
            return $files[0];
        }
        
        $q="INSERT INTO files (path, name, description, type, gallery_id) 
            VALUES (:path, :name, :descr, :type, :gid)
        ";
        $sth = $this->dbh->prepare($q);
        $sth->execute([ 
            ":path" => $path,
            ":name" => $file->getName(),
            ":descr" => null,
            ":type" => $file->getType(),
            ":gid" => ( $file instanceof GalleryFile ? $file->getGalleryId() : null ),
        ]);
        $id = $this->dbh->lastInsertId();
        return $this->getFileById($id);
    }
    
    public function getFilesLinkedToGallery(int $galleryId){
        $q="SELECT id, path, name, description, type, gallery_id 
            FROM files 
            WHERE gallery_id = :gid
        ";
        return $this->queryFiles($q, [ ":gid"=>$galleryId ]);
    }

    public function updateFile(File $file){
        $this->requireFileId($file);

        $taintGetter = [
            "name" => function ($f){ return $f->getName(); },
            "gallery_id" => function ($f){ return $f->getGalleryId(); },
        ];
        $taintAttrMap = [
            "name" => "name",
            "gallery_id" => "gallery_id",
        ];
        
        $tainted = $file->getTainted();
        $params = [];
        $setStm = [];
        
        foreach( $taintAttrMap as $taint => $attr ){
            if( isset($tainted[$taint]) ){
                $params[ ":$attr" ] = $taintGetter[$taint]($file);
                $setStm[] = "$attr = :$attr";
            }
        }
        
        if( count($setStm) == 0 ){
            return;
        }
        
        $qset = implode(', ', $setStm);
        $q = "UPDATE files SET $qset WHERE id = :id";
        $params[":id"] = $file->getId();
        
        $sth = $this->dbh->prepare($q);
        $sth->execute($params);
    }
    
    public function galleryFileLoad(GalleryFile $file){
        $id = $file->getGalleryId();
        if( $id === null ){
            throw new DatabaseError("galleryId must be set");
        }
        
        $ga = $this->getGalleryById($id);
        if( $ga === null ){
            throw new DatabaseError("Gallery with id $id not found");
        }
        $this->galleryLoadThumbnails($ga);
        $file->setGallery($ga);
        return $ga;
    }
    
}
