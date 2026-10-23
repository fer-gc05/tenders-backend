create database if not exists tenders; -- creacion de la base de datos tenders si esta no existe

use tenders; -- utilizacion de la base de datos tenders

-- tabla actividades(activities), que contiene la informacion de los productos y servicios que se pueden licitar
create table activities (
    id int auto_increment primary key,
    segment_code int not null,
    segment_name varchar(200) not null,
    family_code int not null,
    family_name varchar(200) not null,
    class_code int not null,
    class_name varchar(200) not null,
    product_code varchar(200) not null,
    product_name varchar(200) not null,
    created_at datetime default current_timestamp, -- Fecha de creacion(se asigna automaticamente al momento de la insercion)
    updated_at datetime default current_timestamp on update current_timestamp, -- Fecha de actualizacion(se asigna automaticamente al momento de la insercion y se actualiza al momento de la modificacion)
    index idx_product_code (product_code)
);

-- tabla ofertas, convertida a licitaciones(tenders) para mayor facilidad de entendimiento
create table tenders (
    id int auto_increment primary key,
    consecutive_code varchar(50) not null unique, -- consecutivo de la licitacion, valor unico para cada licitacion
    subject varchar(150) not null,
    description varchar(400) not null,
    currency varchar(3) not null, -- moneda de la licitacion, ejemplo de uso: COP, USD, EUR
    budget decimal(15,2) not null, -- presupuesto de la licitacion, ejemplo de uso: 1000000.00 (extension de 15 digitos, 2 decimales)
    activity_id int not null, -- llave foranea hacia la tabla actividades
    start_date date not null,
    start_time time not null,
    close_date date not null,
    close_time time not null,
    status varchar(20) not null default 'draft',
    created_at datetime default current_timestamp, -- Fecha de creacion(se asigna automaticamente al momento de la insercion)
    updated_at datetime default current_timestamp on update current_timestamp, -- Fecha de actualizacion(se asigna automaticamente al momento de la insercion y se actualiza al momento de la modificacion)
    foreign key (activity_id) references activities(id)
);

-- tabla licitaciones_documentos, que contiene la informacion de los documentos asociados a cada licitacion
create table tender_documents (
    id int auto_increment primary key,
    tender_id int not null, -- llave foranea hacia la tabla licitaciones
    title varchar(100) not null,
    file_path varchar(255) not null, -- ruta del archivo en el servidor
    created_at datetime default current_timestamp, -- Fecha de creacion(se asigna automaticamente al momento de la insercion)
    updated_at datetime default current_timestamp on update current_timestamp, -- Fecha de actualizacion(se asigna automaticamente al momento de la insercion y se actualiza al momento de la modificacion)
    foreign key (tender_id) references tenders(id)
);

-- Orden de creacion de las tablas: primero se crea la tabla activities, luego tenders y finalmente tender_documents, ya que tenders depende de activities y tender_documents depende de tenders.
